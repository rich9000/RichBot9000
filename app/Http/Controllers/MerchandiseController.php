<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShoppingCart;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class MerchandiseController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return view('merchendise.index', compact('products'));
    }

    public function show(Product $product)
    {
        return view('merchendise.product', compact('product'));
    }

    public function checkout()
    {
        $cart = ShoppingCart::where('user_id', auth()->id())
            ->with('product')
            ->get();

        return view('merchendise.checkout', compact('cart'));
    }

    public function addToCart(Request $request, Product $product)
    {
        $quantity = $request->input('quantity', 1);
        
        $cartItem = ShoppingCart::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'product_id' => $product->id
            ],
            [
                'quantity' => $quantity
            ]
        );

        return response()->json([
            'message' => 'Product added to cart',
            'cart' => $cartItem
        ]);
    }

    public function removeFromCart(Product $product)
    {
        ShoppingCart::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->delete();

        return response()->json([
            'message' => 'Product removed from cart'
        ]);
    }

    public function updateCart(Request $request, Product $product)
    {
        $quantity = $request->input('quantity');
        
        ShoppingCart::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->update(['quantity' => $quantity]);

        return response()->json([
            'message' => 'Cart updated'
        ]);
    }

    public function createPaymentIntent(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $cart = ShoppingCart::where('user_id', auth()->id())
            ->with('product')
            ->get();

        $total = $cart->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        $shipping = 5.00;
        $tax = $total * 0.08;
        $finalTotal = $total + $shipping + $tax;

        $paymentIntent = PaymentIntent::create([
            'amount' => round($finalTotal * 100),
            'currency' => 'usd',
            'metadata' => [
                'user_id' => auth()->id()
            ]
        ]);

        return response()->json([
            'clientSecret' => $paymentIntent->client_secret
        ]);
    }

    public function processOrder(Request $request)
    {
        $request->validate([
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'address' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'payment_intent_id' => 'required'
        ]);

        $cart = ShoppingCart::where('user_id', auth()->id())
            ->with('product')
            ->get();

        $total = $cart->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        $shipping = 5.00;
        $tax = $total * 0.08;
        $finalTotal = $total + $shipping + $tax;

        $order = Order::create([
            'user_id' => auth()->id(),
            'total_amount' => $finalTotal,
            'shipping_address' => $request->address,
            'shipping_city' => $request->city,
            'shipping_state' => $request->state,
            'shipping_zip' => $request->zip,
            'stripe_payment_id' => $request->payment_intent_id
        ]);

        foreach ($cart as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price_at_time' => $item->product->price
            ]);

            // Update product stock
            $item->product->decrement('stock_quantity', $item->quantity);
        }

        // Clear the cart
        ShoppingCart::where('user_id', auth()->id())->delete();

        return response()->json([
            'order_id' => $order->id,
            'message' => 'Order processed successfully'
        ]);
    }

    public function confirmation(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        return view('merchendise.confirmation', compact('order'));
    }
} 