<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The RichBOT 9000</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Custom CSS for Ominous Feel -->
    <style>
        body {
            background-color: #1a1a1a;
            color: #e0e0e0;
            font-family: 'Figtree', sans-serif;
        }
        .navbar, .dropdown-menu {
            background-color: #2c2c2c;
        }
        .navbar-brand, .nav-link, .dropdown-item {
            color: #e0e0e0 !important;
        }
        .navbar-brand:hover, .nav-link:hover, .dropdown-item:hover {
            color: #ff2d20 !important;
        }
        .btn-primary {
            background-color: #ff2d20;
            border-color: #ff2d20;
        }
        .btn-primary:hover {
            background-color: #e0261a;
            border-color: #d01c17;
        }
        .assistant-prompt, .remote-richbot {
            background-color: #333333;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .assistant-prompt h2, .remote-richbot h2 {
            color: #ff2d20;
        }
    </style>

    <!-- Scripts -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- jQuery UI -->
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js" integrity="sha256-sw0iNNXmOJbQhYFuC9OF2kOlD5KQKe1y5lfBn4C9Sjg=" crossorigin="anonymous"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="#">The Rich Bot 9000</a>

        <div class="" id="">

            <!--
            @guest
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Login</a>
                    </li>
                </ul>
            @else
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Calendar</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                           data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Pulldown Menu <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="#">Menu Item</a></li>
                        </ul>
                    </li>
                    @if(auth()->user()->hasRole('Admin'))
                        <li class="nav-item dropdown">
                            <a id="adminDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                               data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Admin <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                                <li><a class="dropdown-item" href="{{ route('users.index') }}">Users</a></li>
                            </ul>
                        </li>
                    @endif
                </ul>
                <div class="d-inline-block ms-3">
                    <a id="userDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ Auth::user()->name }} <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">User Profile</a></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                Logout
                            </a>
                        </li>
                    </ul>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            @endguest


                        -->
            <!-- Launch WebApp Button -->
            <ul class="navbar-nav ms-3">
                <li class="nav-item">
                    <a href="https://richbot9000.com/webapp" target="_blank" class="btn btn-primary">Launch WebApp</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    @yield('content')
</div>





<style>
    footer img {
        opacity: 0.8;
        transition: opacity 0.3s ease;
    }

    footer img:hover {
        opacity: 1;
    }
</style>


<!-- Footer Section -->
<footer class="bg-dark text-light py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p>&copy; {{ date('Y') }} Richbot9000. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <img src="{{ asset('images/benevolent-robot-overlord.png') }}" alt="Benevolent Robot Overlord" class="img-fluid" style="max-width: 100px;">
            </div>
        </div>
    </div>
</footer>



@stack('modals')
@stack('scripts')

</body>
</html>
