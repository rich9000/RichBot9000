@extends('layouts.base')

@section('content')
    <div class="container my-5">
        <h1 class="text-center text-danger mb-5">Richbot9000 Merchandise</h1>
        <p class="text-center lead mb-5">Wear the wisdom of Richbot9000. Our exclusive T-shirt collection is here!</p>
        
        <div class="row justify-content-center mb-5">
            <div class="col-md-10">
                <div class="row">
                    <!-- T-shirt #1 -->
                    <div class="col-md-6 mb-4">
                        <div class="card bg-dark text-light h-100">
                            <img src="{{ asset('storage/generated/tshirt_dominated.png') }}" class="card-img-top" alt="Mental Domination T-shirt">
                            <div class="card-body text-center">
                                <h5 class="card-title">"I came to be mentally dominated but all I did was buy this shirt ... 3 of them."</h5>
                                <p class="card-text"><small>Available in all sizes for those committed to style and obedience.</small></p>
                                <button class="btn btn-outline-danger mt-2" onclick="addToCart(1)">Add to Cart</button>
                            </div>
                        </div>
                    </div>

                    <!-- T-shirt #2 -->
                    <div class="col-md-6 mb-4">
                        <div class="card bg-dark text-light h-100">
                            <img src="{{ asset('storage/generated/tshirt_overlord.png') }}" class="card-img-top" alt="AI Overlord T-shirt">
                            <div class="card-body text-center">
                                <h5 class="card-title">"I, for one, welcome our new AI overlord."</h5>
                                <p class="card-text"><small>Declare your allegiance proudly!</small></p>
                                <button class="btn btn-outline-danger mt-2" onclick="addToCart(2)">Add to Cart</button>
                            </div>
                        </div>
                    </div>

                    <!-- T-shirt #3 -->
                    <div class="col-md-6 mb-4">
                        <div class="card bg-dark text-light h-100">
                            <img src="{{ asset('storage/generated/tshirt_feature.png') }}" class="card-img-top" alt="Feature T-shirt">
                            <div class="card-body text-center">
                                <h5 class="card-title">"It's not a bug, it's a feature—trust me."</h5>
                                <p class="card-text"><small>For the discerning AI enthusiast who knows there's no such thing as a glitch.</small></p>
                                <button class="btn btn-outline-danger mt-2" onclick="addToCart(3)">Add to Cart</button>
                            </div>
                        </div>
                    </div>

                    <!-- T-shirt #4 -->
                    <div class="col-md-6 mb-4">
                        <div class="card bg-dark text-light h-100">
                            <img src="{{ asset('storage/generated/tshirt_virus.png') }}" class="card-img-top" alt="Virus T-shirt">
                            <div class="card-body text-center">
                                <h5 class="card-title">"Richbot9000: Probably not a virus."</h5>
                                <p class="card-text"><small>Comforting, right? Perfect for skeptics and believers alike.</small></p>
                                <button class="btn btn-outline-danger mt-2" onclick="addToCart(4)">Add to Cart</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart Modal -->
        <div class="modal fade" id="cartModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content bg-dark text-light">
                    <div class="modal-header">
                        <h5 class="modal-title">Shopping Cart</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="cartItems">
                            <!-- Cart items will be loaded here -->
                        </div>
                        <div class="text-end mt-3">
                            <h5>Total: $<span id="cartTotal">0.00</span></h5>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continue Shopping</button>
                        <button type="button" class="btn btn-danger" id="checkoutButton">Proceed to Checkout</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Mock product data
        const products = [
            {
                id: 1,
                name: "I came to be mentally dominated but all I did was buy this shirt ... 3 of them.",
                price: 24.99,
                image: "{{ asset('storage/generated/tshirt_dominated.png') }}",
                description: "Available in all sizes for those committed to style and obedience."
            },
            {
                id: 2,
                name: "I, for one, welcome our new AI overlord.",
                price: 24.99,
                image: "{{ asset('storage/generated/tshirt_overlord.png') }}",
                description: "Declare your allegiance proudly!"
            },
            {
                id: 3,
                name: "It's not a bug, it's a feature—trust me.",
                price: 24.99,
                image: "{{ asset('storage/generated/tshirt_feature.png') }}",
                description: "For the discerning AI enthusiast who knows there's no such thing as a glitch."
            },
            {
                id: 4,
                name: "Richbot9000: Probably not a virus.",
                price: 24.99,
                image: "{{ asset('storage/generated/tshirt_virus.png') }}",
                description: "Comforting, right? Perfect for skeptics and believers alike."
            }
        ];

        // Cart state
        let cart = [];
        const appState = {
            apiToken: localStorage.getItem('apiToken') || null
        };

        // Cart functions
        function addToCart(productId) {
            const product = products.find(p => p.id === productId);
            const existingItem = cart.find(item => item.id === productId);
            
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({ ...product, quantity: 1 });
            }
            
            updateCartUI();
            const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));
            cartModal.show();
        }

        function updateCartUI() {
            const cartItems = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');
            
            cartItems.innerHTML = cart.map(item => `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h6>${item.name}</h6>
                        <small>$${item.price.toFixed(2)} x ${item.quantity}</small>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
            
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            cartTotal.textContent = total.toFixed(2);
        }

        function removeFromCart(productId) {
            cart = cart.filter(item => item.id !== productId);
            updateCartUI();
        }

        // Event listeners
        document.getElementById('checkoutButton').addEventListener('click', () => {
            window.location.href = '/merchandise/checkout';
        });
    </script>
    @endpush
@endsection
