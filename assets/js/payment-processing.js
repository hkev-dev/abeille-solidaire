export class PaymentProcessor {
    constructor(config) {
        this.stripe = Stripe(config.stripePublicKey);
        this.elements = null;
        this.cardElement = null;
        this.form = null;
        this.config = config;
        this.processing = false;
    }

    initialize() {
        this.elements = this.stripe.elements();
        this.setupStripeElement();
        this.setupPaymentMethodSelection();
        this.setupFormSubmission();
    }

    setupStripeElement() {
        this.cardElement = this.elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a'
                }
            }
        });

        this.cardElement.mount('#card-element');
        this.cardElement.on('change', this.handleCardChange.bind(this));
    }

    setupPaymentMethodSelection() {
        const paymentOptions = document.querySelectorAll('.payment-option');
        const stripeForm = document.getElementById('stripe-payment-form');
        const cryptoForm = document.getElementById('crypto-payment');
        const submitButtons = document.querySelectorAll('button[type="submit"]');

        paymentOptions.forEach(option => {
            option.addEventListener('click', (e) => {
                const method = e.currentTarget.dataset.payment;
                
                // Update UI
                paymentOptions.forEach(opt => opt.classList.remove('selected'));
                e.currentTarget.classList.add('selected');

                // Show/hide payment forms
                stripeForm.style.display = method === 'stripe' ? 'block' : 'none';
                cryptoForm.style.display = method === 'crypto' ? 'block' : 'none';

                // Enable/disable submit buttons based on selection
                submitButtons.forEach(btn => {
                    btn.disabled = false;
                });
            });
        });

        // Setup crypto form submission
        cryptoForm.addEventListener('submit', this.handleCryptoSubmit.bind(this));
    }

    setupFormSubmission() {
        this.form = document.getElementById('stripe-payment-form');
        this.form.addEventListener('submit', this.handleSubmit.bind(this));
    }

    handleCardChange({error}) {
        const displayError = document.getElementById('card-errors');
        if (error) {
            displayError.textContent = error.message;
            displayError.classList.remove('d-none');
        } else {
            displayError.textContent = '';
            displayError.classList.add('d-none');
        }
    }

    async handleSubmit(event) {
        event.preventDefault();

        if (this.processing) return;
        this.processing = true;

        const submitButton = this.form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2"></span>
            Processing...
        `;

        try {
            // First, create the payment intent
            const response = await fetch(this.config.createIntentUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrf.stripeToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    payment_method: 'stripe'
                })
            });

            const data = await response.json();

            if (!response.ok || data.error) {
                throw new Error(data.error || 'Failed to create payment intent');
            }

            // Then confirm the card payment
            const { error: confirmError, paymentIntent } = await this.stripe.confirmCardPayment(
                data.clientSecret,
                {
                    payment_method: {
                        card: this.cardElement,
                        billing_details: {
                            email: this.config.userEmail
                        }
                    }
                }
            );

            if (confirmError) {
                throw confirmError;
            }

            // Payment successful, redirect to waiting room
            window.location.href = this.config.returnUrl;

        } catch (error) {
            this.handlePaymentError(error);
        } finally {
            this.processing = false;
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    }

    async handleCryptoSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;

        try {
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Initializing Payment...
            `;

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrf.cryptoToken
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to initialize cryptocurrency payment');
            }

            // Store transaction ID in session storage for status checking
            if (data.txn_id) {
                sessionStorage.setItem('cp_txn_id', data.txn_id);
            }

            // Redirect to CoinPayments checkout
            window.location.href = data.checkout_url;

        } catch (error) {
            this.handleCryptoError(error);
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    }

    handleCryptoError(error) {
        const errorContainer = document.createElement('div');
        errorContainer.className = 'alert alert-danger mt-3';
        errorContainer.innerHTML = `
            <strong>Payment Error:</strong> ${error.message}
            <br>
            <small>Please try again or choose a different payment method.</small>
        `;

        const form = document.getElementById('crypto-payment');
        const existingError = form.querySelector('.alert');
        if (existingError) {
            existingError.remove();
        }
        form.insertBefore(errorContainer, form.firstChild);

        // Log error for debugging
        console.error('CoinPayments Error:', error);
    }

    handlePaymentError(error) {
        const errorElement = document.getElementById('card-errors');
        errorElement.textContent = error.message || 'An error occurred while processing your payment.';
        errorElement.classList.remove('d-none');
        
        // Optionally trigger analytics
        if (typeof window.reportPaymentError === 'function') {
            window.reportPaymentError(error);
        }
    }

    handlePaymentSuccess(paymentIntent) {
        // Create a loading overlay
        const overlay = document.createElement('div');
        overlay.className = 'payment-processing-overlay';
        overlay.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Processing your payment...</p>
        `;
        document.body.appendChild(overlay);

        // Redirect to waiting room
        window.location.href = this.config.waitingRoomUrl;
    }
}

// Polling functionality for waiting room
export class PaymentStatusPoller {
    constructor(config) {
        this.config = config;
        this.pollInterval = null;
        this.retryCount = 0;
        this.maxRetries = 120; // 10 minutes at 5-second intervals
        this.txnId = sessionStorage.getItem('cp_txn_id');
    }

    startPolling() {
        // Clear any existing intervals
        this.stopPolling();
        
        // Start new polling interval
        this.pollInterval = setInterval(() => this.checkStatus(), 5000);
        
        // Add event listener for page visibility
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
    }

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    handleVisibilityChange() {
        if (document.hidden) {
            this.stopPolling();
        } else {
            this.startPolling();
        }
    }

    async checkStatus() {
        if (this.retryCount >= this.maxRetries) {
            this.stopPolling();
            this.handleTimeout();
            return;
        }

        try {
            const response = await fetch(this.config.statusCheckUrl, {
                headers: {
                    'X-Transaction-ID': this.txnId || ''
                }
            });
            const data = await response.json();

            if (data.status === 'completed' && data.redirect) {
                this.stopPolling();
                sessionStorage.removeItem('cp_txn_id');
                window.location.href = data.redirect;
            } else if (data.status === 'failed') {
                this.stopPolling();
                sessionStorage.removeItem('cp_txn_id');
                window.location.href = this.config.failureUrl;
            } else if (data.status === 'expired') {
                this.stopPolling();
                sessionStorage.removeItem('cp_txn_id');
                this.handleExpired();
            }

            this.retryCount++;
        } catch (error) {
            console.error('Error checking payment status:', error);
        }
    }

    handleTimeout() {
        const messageElement = document.querySelector('.waiting-room-message');
        if (messageElement) {
            messageElement.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Payment verification is taking longer than expected. Please contact support if you have completed the payment.
                </div>
            `;
        }
    }

    handleExpired() {
        window.location.href = this.config.failureUrl + '?reason=expired';
    }
}
