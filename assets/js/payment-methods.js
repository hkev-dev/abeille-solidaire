import { Stripe } from '@stripe/stripe-js';

export class PaymentMethodManager {
    constructor(stripePublicKey) {
        this.stripe = null;
        this.card = null;
        this.stripePublicKey = stripePublicKey;
        this.initializeModal();
        this.initializeStripe();
    }

    async initializeStripe() {
        this.stripe = await Stripe(this.stripePublicKey);
        const elements = this.stripe.elements();
        
        this.card = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#1e3737',
                    '::placeholder': {
                        color: '#6e7a7a'
                    }
                }
            }
        });
    }

    initializeModal() {
        const modal = document.getElementById('payment-method-modal');
        const form = document.getElementById('payment-method-form');
        const typeInputs = document.querySelectorAll('input[name="payment_method[type]"]');
        
        typeInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                document.querySelectorAll('.payment-method-form').forEach(el => el.classList.add('hidden'));
                document.getElementById(`${e.target.value}-form`).classList.remove('hidden');
                
                if (e.target.value === 'card') {
                    this.mountCardElement();
                }
            });
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const type = document.querySelector('input[name="payment_method[type]"]:checked').value;
            
            if (type === 'card') {
                await this.handleCardSubmission(e);
            } else {
                await this.handleCryptoSubmission(e);
            }
        });
    }

    mountCardElement() {
        const element = document.getElementById('card-element');
        if (!this.card) return;
        
        this.card.mount(element);
        this.card.addEventListener('change', ({error}) => {
            const displayError = document.getElementById('card-errors');
            if (error) {
                displayError.textContent = error.message;
                displayError.classList.remove('hidden');
            } else {
                displayError.textContent = '';
                displayError.classList.add('hidden');
            }
        });
    }

    async handleCardSubmission(e) {
        e.preventDefault();
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');        
        submitButton.disabled = true;
        
        try {
            const {token, error} = await this.stripe.createToken(this.card);
            
            if (error) {
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = error.message;
                submitButton.disabled = false;
                return;
            }
            
            const tokenInput = document.createElement('input');
            tokenInput.setAttribute('type', 'hidden');
            tokenInput.setAttribute('name', 'stripeToken');
            tokenInput.setAttribute('value', token.id);
            form.appendChild(tokenInput);
            
            form.submit();
        } catch (error) {
            console.error('Error:', error);
            submitButton.disabled = false;
        }
    }

    async handleCryptoSubmission(e) {
        e.preventDefault();
        const form = e.target;
        form.submit();
    }
}
