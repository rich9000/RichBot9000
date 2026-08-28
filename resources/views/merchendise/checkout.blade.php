<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - RichBot9000</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/merchandise">RichBot9000 Shop</a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-8">
                <h2 class="mb-4">Checkout</h2>
                
                <!-- Shipping Information -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Shipping Information</h5>
                        <form id="shipping-form">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="state" class="form-label">State</label>
                                    <input type="text" class="form-control" id="state" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="zip" class="form-label">ZIP</label>
                                    <input type="text" class="form-control" id="zip" required>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Payment Information</h5>
                        <div id="card-element" class="mb-3">
                            <!-- Stripe Card Element will be inserted here -->
                        </div>
                        <div id="card-errors" class="text-danger" role="alert"></div>
                    </div>
                </div>

                <button class="btn btn-primary btn-lg w-100" id="submit-button">Place Order</button>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        <div id="order-items">
                            <!-- Order items will be loaded here -->
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span id="subtotal">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span id="shipping">$5.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax</span>
                            <span id="tax">$0.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total</strong>
                            <strong id="total">$0.00</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mock cart data
        const cart = [
            {
                id: 1,
                name: "RichBot9000 T-Shirt",
                price: 24.99,
                quantity: 2
            },
            {
                id: 2,
                name: "RichBot9000 Sticker Pack",
                price: 9.99,
                quantity: 1
            }
        ];

        // Initialize Stripe
        const stripe = Stripe('pk_test_your_publishable_key');
        const elements = stripe.elements();
        const card = elements.create('card');
        card.mount('#card-element');

        // Handle card errors
        card.addEventListener('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Calculate order totals
        function calculateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const shipping = 5.00;
            const tax = subtotal * 0.08; // 8% tax rate
            const total = subtotal + shipping + tax;

            document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('shipping').textContent = `$${shipping.toFixed(2)}`;
            document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
            document.getElementById('total').textContent = `$${total.toFixed(2)}`;

            return total;
        }

        // Render order items
        function renderOrderItems() {
            const orderItems = document.getElementById('order-items');
            orderItems.innerHTML = cart.map(item => `
                <div class="d-flex justify-content-between mb-2">
                    <div>
                        <h6>${item.name}</h6>
                        <small>Quantity: ${item.quantity}</small>
                    </div>
                    <div>
                        $${(item.price * item.quantity).toFixed(2)}
                    </div>
                </div>
            `).join('');
        }

        // Handle form submission
        document.getElementById('submit-button').addEventListener('click', async function(event) {
            event.preventDefault();

            const submitButton = document.getElementById('submit-button');
            submitButton.disabled = true;
            submitButton.textContent = 'Processing...';

            try {
                // Create payment intent on the server
                const response = await fetch('/api/create-payment-intent', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${appState.apiToken}`
                    },
                    body: JSON.stringify({
                        amount: Math.round(calculateTotals() * 100), // Convert to cents
                        currency: 'usd'
                    })
                });

                const data = await response.json();

                // Confirm the payment
                const result = await stripe.confirmCardPayment(data.clientSecret, {
                    payment_method: {
                        card: card,
                        billing_details: {
                            name: `${document.getElementById('firstName').value} ${document.getElementById('lastName').value}`,
                            email: document.getElementById('email').value,
                            address: {
                                line1: document.getElementById('address').value,
                                city: document.getElementById('city').value,
                                state: document.getElementById('state').value,
                                postal_code: document.getElementById('zip').value
                            }
                        }
                    }
                });

                if (result.error) {
                    const errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                    submitButton.disabled = false;
                    submitButton.textContent = 'Place Order';
                } else {
                    // Payment successful
                    window.location.href = '/merchandise/confirmation?order_id=' + data.orderId;
                }
            } catch (error) {
                console.error('Error:', error);
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = 'An error occurred. Please try again.';
                submitButton.disabled = false;
                submitButton.textContent = 'Place Order';
            }
        });

        // Initialize
        renderOrderItems();
        calculateTotals();
    </script>
</body>
</html> 