export class PaymentProcessor {
    constructor(config) {
        this.stripe = Stripe(config.stripePublicKey);
        this.elements = null;
        this.cardElement = null;
        this.form = null;
        this.config = config;
        this.processing = false;
        this.baseAmount = 25; // Registration fee
        this.membershipAmount = 25; // Annual membership fee
        this.currentAmount = this.baseAmount;
    }

    initialize() {
        this.elements = this.stripe.elements();
        this.setupStripeElement();
        this.setupPaymentMethodSelection();
        this.setupFormSubmission();
        // Add this line to load cryptocurrencies on initialization
        this.loadCryptoCurrencies();
        this.setupMembershipToggle();
        this.setupMonthlyToggle();
    }

    setupMonthlyToggle() {
        const monthlyCheckbox = document.getElementById('payment_supplementary_subscibe');
        const cryptoCard = document.querySelector('.payment-option[data-payment="crypto"]');
        const cryptoRadio = cryptoCard.querySelector('input[type="radio"]');
        const stripeRadio = document.querySelector('.payment-option[data-payment="stripe"] input[type="radio"]');

        if (!monthlyCheckbox) return;

        if (!document.getElementById('monthly-toggle-style')) {
            const style = document.createElement('style');
            style.id = 'monthly-toggle-style';
            style.textContent = `.payment-option.disabled { opacity: 0.5; pointer-events: none; }`;
            document.head.append(style);
        }

        const update = () => {
            if (monthlyCheckbox.checked) {
                cryptoRadio.disabled = true;
                cryptoCard.classList.add('disabled');
                if (cryptoRadio.checked) {
                    cryptoRadio.checked = false;
                    cryptoRadio.dispatchEvent(new Event('change'));
                    stripeRadio.checked = true;
                    stripeRadio.dispatchEvent(new Event('change'));
                }
            } else {
                cryptoRadio.disabled = false;
                cryptoCard.classList.remove('disabled');
            }
        };

        monthlyCheckbox.addEventListener('change', update);
        update();
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
        const currencySelect = document.getElementById('crypto-currency-select');
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

                // Load currencies if crypto is selected
                if (method === 'crypto' && currencySelect) {
                    this.loadCryptoCurrencies();
                }

                // Enable/disable submit buttons based on selection
                submitButtons.forEach(btn => {
                    btn.disabled = false;
                });
            });
        });

        // Setup crypto form submission with proper validation
        if (cryptoForm) {
            cryptoForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const currency = currencySelect ? currencySelect.value : null;
                if (!currency) {
                    this.handleCryptoError(new Error('Please select a cryptocurrency'));
                    return;
                }
                this.handleCryptoSubmit(event);
            });
        }
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
        const includeMembership = document.querySelector('[name="payment_selection[include_annual_membership]"]');
        const checkbox = document.getElementById('payment_supplementary_subscibe');
        const isMonthly = checkbox ? checkbox.checked : false;

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
                    payment_method: 'stripe',
                    isMonthly: isMonthly,
                    include_annual_membership: includeMembership ? includeMembership.checked : false
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

            console.log(data);

            if(data.subscriptionId) {
               window.location.href = this.config.waitingSubRoomUrl + "?subscriptionId=" + data.subscriptionId;
            } else {
                window.location.href = this.config.returnUrl + "?id=" + data.entityId;
            }
                

            // Payment successful, redirect to waiting room
            //window.location.href = this.config.returnUrl + "?id=" + data.entityId;

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
        const currencySelect = document.getElementById('crypto-currency-select');
        const checkbox = document.getElementById('payment_supplementary_subscibe');
        const isMonthly = checkbox ? checkbox.checked : false;
        
        if (!currencySelect || !currencySelect.value) {
            this.handleCryptoError(new Error('Please select a cryptocurrency'));
            return;
        }

        const selectedCurrency = currencySelect.value;
        const originalText = submitButton.innerHTML;

        try {
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Initializing Payment...
            `;

            let includeMembership = false;

            console.log("includeMembership")
            if (document.querySelector('[name="payment_selection[include_annual_membership]"]')) {
                includeMembership =  document.querySelector('[name="payment_selection[include_annual_membership]"]').checked;
            }

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrf.cryptoToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    payment_method: 'coinpayments',
                    isMonthly: isMonthly,
                    currency: selectedCurrency,
                    include_annual_membership: includeMembership ? includeMembership : false
                })
            });

            const data = await response.json();

            if (!response.ok || data.error) {
                throw new Error(data.error || 'Failed to initialize cryptocurrency payment');
            }

            // Store transaction details in session storage
            sessionStorage.setItem('cp_txn_id', data.txn_id);
            sessionStorage.setItem('cp_status_url', data.status_url);
            sessionStorage.setItem('coinpayments_data', data);
            const queryString = encodeURIComponent(JSON.stringify(data));

            window.location.href = this.config.returnUrl + "?id=" + data.entityId + "&cp_data=" +queryString;

        } catch (error) {
            this.handleCryptoError(error);
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    }

    showCryptoPaymentModal(paymentData) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'cryptoPaymentModal';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Complete Cryptocurrency Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="qr-code mb-3">
                            <img src="${paymentData.qrcode_url}" 
                                 alt="Payment QR Code">
                        </div>
                        <div class="payment-details">
                            <div class="alert alert-warning mb-3">
                                <strong>Important:</strong> Send exactly the specified amount to ensure proper processing
                            </div>
                            <p class="mb-2">Amount to send: <strong>${paymentData.amount} ${paymentData.currency}</strong></p>
                            <p class="mb-2">To address:<br><code class="select-all">${paymentData.address}</code></p>
                            <button class="btn btn-sm btn-secondary mb-3" onclick="navigator.clipboard.writeText('${paymentData.address}')">
                                Copy Address
                            </button>
                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Payment will be confirmed after ${paymentData.confirms_needed} network confirmations<br>
                                    Transaction will expire in ${Math.floor(paymentData.timeout / 60)} minutes
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="${paymentData.status_url}" target="_blank" class="btn btn-info">
                            <i class="fas fa-external-link-alt me-1"></i>
                            Check Payment Status
                        </a>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        // Start status polling
        this.startPaymentStatusPolling(paymentData.txn_id);
    }

    async loadCryptoCurrencies() {
        try {
            const response = await fetch(`${this.config.currenciesUrl}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();
            
            if (!data.currencies) {
                throw new Error('No currencies available');
            }

            const selectElement = document.getElementById('crypto-currency-select');
            if (!selectElement) return;

            // Sort currencies by name
            const sortedCurrencies = Object.entries(data.currencies)
                .sort(([,a], [,b]) => a.name.localeCompare(b.name));

            selectElement.innerHTML = '<option value="">Select a cryptocurrency...</option>';

            sortedCurrencies.forEach(([code, details]) => {
                // Skip if it's a fiat currency or not accepted
                if (details.is_fiat === 1) return;
                
                const option = document.createElement('option');
                option.value = code;
                option.dataset.rate = details.rate_btc;
                option.dataset.fee = details.tx_fee;
                option.dataset.confirms = details.confirms_needed;
                option.textContent = `${details.name} (${code}) - Fee: ${details.tx_fee} ${code}`;
                selectElement.appendChild(option);
            });

            // Enable form and add change handler
            const form = document.getElementById('crypto-payment');
            form.querySelector('button[type="submit"]').disabled = false;
            
            selectElement.addEventListener('change', this.handleCurrencySelection.bind(this));

        } catch (error) {
            console.error('Failed to load cryptocurrencies:', error);
            this.handleCryptoError(error);
        }
    }

    handleCurrencySelection(event) {
        const selected = event.target.options[event.target.selectedIndex];
        const detailsContainer = document.querySelector('.currency-details');
        
        if (!selected.value) {
            detailsContainer.style.display = 'none';
            return;
        }

        // Update details display
        detailsContainer.style.display = 'block';
        detailsContainer.querySelector('.rate-display').textContent = 
            `1 ${selected.value} = ${selected.dataset.rate} BTC`;
        detailsContainer.querySelector('.fee-display').textContent = 
            `${selected.dataset.fee} ${selected.value}`;
        detailsContainer.querySelector('.confirms-display').textContent = 
            `${selected.dataset.confirms} blocks`;

        // Calculate estimated total including network fee
        const amountInBTC = this.config.amount * parseFloat(selected.dataset.rate);
        const totalWithFee = amountInBTC + parseFloat(selected.dataset.fee);
        detailsContainer.querySelector('.total-display').textContent = 
            `${totalWithFee.toFixed(8)} ${selected.value}`;
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

        console.log('success');

        // Redirect to waiting room
        //window.location.href = this.config.waitingRoomUrl;
    }

    setupMembershipToggle() {
        const membershipCheckbox = document.querySelector('[name="payment_selection[include_annual_membership]"]');
        const membershipRow = document.getElementById('membership-row');
        const totalAmount = document.getElementById('total-amount');
        const stripeButton = document.querySelector('#stripe-payment-form button');
        const cryptoButton = document.querySelector('#crypto-payment button');

        if (!membershipCheckbox) return; // Exit if checkbox not found

        membershipCheckbox.addEventListener('change', (e) => {
            this.currentAmount = e.target.checked ? 
                this.baseAmount + this.membershipAmount : 
                this.baseAmount;

            // Update UI elements with null checks
            if (membershipRow) {
                membershipRow.style.display = e.target.checked ? 'table-row' : 'none';
            }
            if (totalAmount) {
                totalAmount.textContent = `${this.currentAmount.toFixed(2)} €`;
            }
            
            // Update button texts with null checks
            if (stripeButton) {
                stripeButton.textContent = `Payer ${this.currentAmount.toFixed(2)}€ par Carte`;
            }
            if (cryptoButton) {
                cryptoButton.textContent = `Continuer avec le Paiement en Cryptomonnaie (${this.currentAmount.toFixed(2)}€)`;
            }

            // Update crypto currency conversion if active
            if (this.lastSelectedCurrency) {
                this.updateCryptoAmount(this.lastSelectedCurrency);
            }
        });
    }

    updateCryptoAmount(currencyDetails) {
        // Store the last selected currency for recalculation when membership toggle changes
        this.lastSelectedCurrency = currencyDetails;
        
        const amount = this.currentAmount;
        const rate = parseFloat(currencyDetails.dataset.rate);
        const fee = parseFloat(currencyDetails.dataset.fee);
        
        const totalCrypto = (amount * rate) + fee;
        
        const detailsContainer = document.querySelector('.currency-details');
        if (detailsContainer) {
            detailsContainer.querySelector('.total-display').textContent = 
                `${totalCrypto.toFixed(8)} ${currencyDetails.value}`;
            // ...update other currency details...
        }
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
