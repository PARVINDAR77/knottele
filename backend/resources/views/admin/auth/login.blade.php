<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KNOTELLE Admin</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- TailwindCSS with safe Vite fallback -->
    @if (file_exists(public_path('build/manifest.json')))
        @vite('resources/css/app.css')
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f0f9ff 0%, #fafaf9 50%, #f5f3ff 100%);
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(220, 38, 38, 0.03) 0%, transparent 55%),
                radial-gradient(circle at 85% 30%, rgba(236, 72, 153, 0.02) 0%, transparent 55%),
                radial-gradient(circle at 50% 80%, rgba(20, 184, 166, 0.02) 0%, transparent 55%);
            z-index: -1;
        }
        
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(224, 242, 254, 0.5);
            box-shadow: 
                0 20px 60px rgba(220, 38, 38, 0.08),
                0 8px 24px rgba(28, 25, 23, 0.06),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            transition: all 0.4s ease;
        }
        
        .login-card:hover {
            box-shadow: 
                0 25px 70px rgba(220, 38, 38, 0.12),
                0 10px 30px rgba(28, 25, 23, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 14px 24px;
            border-radius: 14px;
            transition: all 0.3s ease;
            box-shadow: 
                0 6px 20px rgba(220, 38, 38, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s;
        }
        
        .btn-primary:hover::after {
            left: 100%;
        }
        
        .btn-primary:hover {
            box-shadow: 
                0 10px 25px rgba(220, 38, 38, 0.35),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
            transform: translateY(-3px);
        }
        
        .btn-primary:active {
            transform: translateY(-1px);
            box-shadow: 
                0 5px 15px rgba(220, 38, 38, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }
        
        .input-field {
            background-color: rgba(248, 250, 252, 0.8);
            border: 1.5px solid #e2e8f0;
            transition: all 0.25s ease;
            color: #1c1917;
        }
        
        .input-field::placeholder {
            color: #a8a29e;
        }
        
        .input-field:hover {
            background-color: rgba(255, 255, 255, 0.9);
            border-color: #bae6fd;
        }
        
        .input-field:focus {
            background-color: #ffffff;
            border-color: #dc2626;
            box-shadow: 
                0 0 0 4px rgba(220, 38, 38, 0.15),
                0 2px 8px rgba(220, 38, 38, 0.1);
            outline: none;
        }
        
        .error-alert {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fecaca;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-3px); }
            40%, 80% { transform: translateX(3px); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        .brand-highlight {
            background: linear-gradient(135deg, #dc2626 0%, #7dd3fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .logo-container {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 24px;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }
        
        .logo-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(220, 38, 38, 0.05) 0%, transparent 70%);
        }
        
        .floating-shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.08), rgba(220, 38, 38, 0.03));
            z-index: -1;
        }
        
        .shape-1 {
            width: 300px;
            height: 300px;
            top: -150px;
            right: -150px;
        }
        
        .shape-2 {
            width: 200px;
            height: 200px;
            bottom: -100px;
            left: -100px;
        }
        
        .security-note {
            background: linear-gradient(135deg, #f0f9ff 0%, #ecfeff 100%);
            border: 1px solid #cffafe;
            border-radius: 12px;
            padding: 16px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .login-card {
                margin: 12px;
            }
            
            .logo-container {
                padding: 20px;
            }
            
            .btn-primary {
                padding: 16px 24px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <!-- Floating background shapes -->
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        
        <!-- Login Card -->
        <div class="login-card rounded-3xl max-w-md w-full animate-fade-in">
            <!-- Logo Header -->
            <div class="p-8 text-center border-b border-red-50/50">
                <div class="logo-container mb-6 flex justify-center items-center">
                    <img src="{{ asset('images/logo/Logo_1.png') }}" class="w-48 mx-auto h-auto relative z-10 object-contain" alt="KNOTELLE Logo">
                </div>
                <h1 class="text-3xl font-bold text-stone-900 mb-2">
                    Admin <span class="brand-highlight">Portal</span>
                </h1>
                <p class="text-stone-500 font-medium">Handcrafted Crochet Boutique Management Dashboard</p>
            </div>

            <!-- Login Form -->
            <div class="p-8">
                @if ($errors->any())
                    <div class="mb-6 p-4 error-alert rounded-2xl">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 bg-red-100 p-2 rounded-xl mr-3 mt-0.5">
                                <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-red-800 font-bold text-sm mb-1">Login Failed</h3>
                                <p class="text-red-700 font-medium">{{ $errors->first() }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ url()->current() }}" class="space-y-6" autocomplete="off">
                    @csrf
                    
                    <div>
                        <label for="email" class="block text-sm font-bold text-stone-800 mb-2 ml-1">
                            <i class="fas fa-envelope text-red-500 mr-2 text-xs"></i>Email Address
                        </label>
                        <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-slate-400"></i>
                                    </div>
                                    <input id="email" type="email" name="email" required 
                                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:bg-white transition-all @error('email') border-red-500 @enderror" 
                                        placeholder="admin@example.com" autocomplete="off" autofocus value="{{ old('email') }}">
                                </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2 ml-1">
                            <label for="password" class="block text-sm font-bold text-stone-800">
                                <i class="fas fa-lock text-red-500 mr-2 text-xs"></i>Password
                            </label>
                            <button type="button" id="togglePassword" class="text-xs text-red-600 hover:text-red-800 font-medium">
                                <i class="fas fa-eye mr-1"></i>Show
                            </button>
                        </div>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-stone-400 text-sm"></i>
                            <input type="password" id="password" name="password" required
                                class="input-field w-full pl-11 pr-4 py-4 rounded-2xl text-stone-900 placeholder-stone-500 font-medium"
                                placeholder="Enter your password" 
                                autocomplete="new-password">
                        </div>
                    </div>

                    <!-- Security Note -->
                    <div class="security-note">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-shield-alt text-red-600"></i>
                            </div>
                            <p class="text-stone-600 text-sm font-medium">
                                <span class="font-bold text-stone-800">Secure Login:</span> Your credentials are encrypted and protected.
                            </p>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="btn-primary w-full py-4 text-base font-bold tracking-wide">
                            <i class="fas fa-sign-in-alt mr-2"></i>SIGN IN TO DASHBOARD
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="px-8 py-6 bg-stone-50/60 border-t border-red-50/50 text-center rounded-b-3xl">
                <div class="flex items-center justify-center space-x-4 mb-2">
                    <div class="h-px w-12 bg-stone-200"></div>
                    <i class="fas fa-heart text-red-400 text-sm"></i>
                    <div class="h-px w-12 bg-stone-200"></div>
                </div>
                <div class="mt-4 text-center text-sm text-slate-400">
                <p>
                    <i class="far fa-copyright mr-1"></i>{{ date('Y') }} KNOTELLE • HANDMADE CROCHET CREATIONS
                </p>
            </div>    <p class="text-xs text-stone-400 mt-1 font-medium">Boutique Store Management</p>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash mr-1';
                this.innerHTML = '<i class="fas fa-eye-slash mr-1"></i>Hide';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye mr-1';
                this.innerHTML = '<i class="fas fa-eye mr-1"></i>Show';
            }
        });
        
        // Add focus effect to input fields
        document.querySelectorAll('.input-field').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('ring-2', 'ring-red-100');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('ring-2', 'ring-red-100');
            });
        });
        
        // Form submission loading state
        const form = document.querySelector('form');
        const submitButton = form.querySelector('button[type="submit"]');
        
        form.addEventListener('submit', function() {
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> SIGNING IN...';
            submitButton.disabled = true;
            submitButton.classList.add('opacity-90');
        });
    </script>

    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false,
                background: '#fafaf9',
                color: '#1c1917',
                customClass: {
                    title: 'text-stone-900 font-bold',
                    popup: 'rounded-2xl shadow-2xl border border-red-100'
                }
            });
        </script>
    @endif
</body>

</html>
