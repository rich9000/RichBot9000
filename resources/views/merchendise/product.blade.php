<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details - RichBot9000</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .product-image {
            max-height: 400px;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/merchandise">RichBot9000 Shop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="cartButton">
                            <i class="bi bi-cart3"></i> Cart <span class="badge bg-primary" id="cartCount">0</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-6">
                <img src="" alt="" class="img-fluid product-image" id="productImage">
            </div>
            <div class="col-md-6">
                <h1 id="productName"></h1>
                <p class="lead" id="productPrice"></p>
                <p id="productDescription"></p>
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="quantity" value="1" min="1" style="width: 100px">
                </div>
                <button class="btn btn-primary btn-lg" id="addToCartButton">Add to Cart</button>
            </div>
        </div>
    </div>

    <!-- Cart Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Shopping Cart</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                    <button type="button" class="btn btn-primary" id="checkoutButton">Proceed to Checkout</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mock product data
        const products = [
            {
                id: 1,
                name: "RichBot9000 T-Shirt",
                price: 24.99,
                image: "{{ asset('images/placeholders/tshirt.jpg') }}",
                description: "Premium quality t-shirt with RichBot9000 logo. Made from 100% cotton, this comfortable t-shirt features our iconic logo on the front. Available in multiple sizes."
            },
            {
                id: 2,
                name: "RichBot9000 Sticker Pack",
                price: 9.99,
                image: "{{ asset('images/placeholders/stickers.jpg') }}",
                description: "Set of 5 high-quality vinyl stickers featuring different RichBot9000 designs. Perfect for laptops, water bottles, or any smooth surface. Weather-resistant and long-lasting."
            },
            {
                id: 3,
                name: "RichBot9000 Hoodie",
                price: 39.99,
                image: "{{ asset('images/placeholders/hoodie.jpg') }}",
                description: "Comfortable hoodie with embroidered RichBot9000 logo. Made from premium cotton blend, featuring a kangaroo pocket and adjustable drawstring hood. Perfect for casual wear."
            }
        ];

        // Cart state
        let cart = [];
        const appState = {
            apiToken: localStorage.getItem('apiToken') || null
        };

        // Get product ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const productId = parseInt(urlParams.get('id'));

        // Load product details
        function loadProductDetails() {
            const product = products.find(p => p.id === productId);
            if (!product) {
                window.location.href = '/merchandise';
                return;
            }

            document.getElementById('productName').textContent = product.name;
            document.getElementById('productPrice').textContent = `$${product.price.toFixed(2)}`;
            document.getElementById('productDescription').textContent = product.description;
            document.getElementById('productImage').src = product.image;
            document.getElementById('productImage').alt = product.name;
        }

        // Cart functions
        function addToCart() {
            const product = products.find(p => p.id === productId);
            const quantity = parseInt(document.getElementById('quantity').value);
            
            const existingItem = cart.find(item => item.id === productId);
            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                cart.push({ ...product, quantity });
            }
            
            updateCartUI();
            const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));
            cartModal.show();
        }

        function updateCartUI() {
            const cartCount = document.getElementById('cartCount');
            const cartItems = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');
            
            cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
            
            cartItems.innerHTML = cart.map(item => `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h6>${item.name}</h6>
                        <small>$${item.price.toFixed(2)} x ${item.quantity}</small>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.id})">
                            <i class="bi bi-trash"></i>
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
        document.getElementById('cartButton').addEventListener('click', () => {
            const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));
            cartModal.show();
        });

        document.getElementById('addToCartButton').addEventListener('click', addToCart);

        document.getElementById('checkoutButton').addEventListener('click', () => {
            window.location.href = '/merchandise/checkout';
        });

        // Initialize
        loadProductDetails();
    </script>
</body>
</html> 