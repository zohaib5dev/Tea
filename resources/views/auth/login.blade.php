<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in · Pulse</title>
    <!-- Tailwind via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Inter + Space Grotesk -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300..700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Alpine.js for password toggle -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Space Grotesk', sans-serif; }
        .gold-text {
            background: linear-gradient(145deg, #BF953F, #FCF6B5, #B38728, #FBF5B7, #AA771C);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .gold-ring {
            transition: box-shadow 0.25s ease, transform 0.2s ease;
        }
        .gold-ring:hover {
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.35);
        }
        /* subtle input focus glow */
        input:focus {
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#FDFBF7] text-gray-800 antialiased overflow-x-hidden min-h-screen flex flex-col">

    <!-- Background decorative blobs (golden) -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-amber-200 blur-[120px] opacity-50"></div>
        <div class="absolute bottom-[-5%] right-[-5%] w-[600px] h-[600px] rounded-full bg-yellow-200 blur-[130px] opacity-40"></div>
        <div class="absolute top-[40%] right-[10%] w-[350px] h-[350px] rounded-full bg-orange-100 blur-[100px] opacity-30"></div>
        <div class="absolute top-[20%] left-[30%] w-[250px] h-[250px] rounded-full bg-yellow-300 blur-[90px] opacity-20"></div>
    </div>

 

    <!-- MAIN AUTH SECTION -->
    <main class="flex-1 flex items-center justify-center px-4 py-12 relative z-10">
        <div class="w-full max-w-md">
            <!-- Card with golden accent border -->
            <div class="relative">
                <!-- decorative golden glow behind card -->
                <div class="absolute inset-0 bg-gradient-to-tr from-amber-300 via-yellow-200 to-orange-300 rounded-[36px] blur-[40px] opacity-30 -z-10"></div>

                <div class="bg-white/90 backdrop-blur-xl rounded-[32px] border border-amber-200/70 shadow-xl shadow-amber-100/50 p-8 sm:p-10">

                    <!-- Heading -->
                    <div class="mb-8 text-center">
                        <h1 class="font-display text-3xl font-bold text-gray-900">
                            Welcome <span class="gold-text">back</span>
                        </h1>
                        <p class="mt-2 text-sm text-gray-500">Sign in to pick up where you left off.</p>
                    </div>

                    <!-- Session status -->
                    @if (session('status'))
                        <div class="mb-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    <!-- Form -->
                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <!-- Email -->
                        <div>
                            <label for="email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-amber-700/80">Email</label>
                            <div class="relative">
                                <i class="fa-regular fa-envelope pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-500/70 text-sm"></i>
                                <input
                                    id="email" type="email" name="email" value="{{ old('email') }}"
                                    required autofocus autocomplete="username"
                                    placeholder="you@example.com"
                                    class="w-full rounded-xl border border-amber-200/80 bg-white/80 py-3 pl-11 pr-4 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-300/30"
                                >
                            </div>
                            @error('email')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div x-data="{ show: false }">
                            <div class="mb-1.5 flex items-center justify-between">
                                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-amber-700/80">Password</label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700 transition">Forgot?</a>
                                @endif
                            </div>
                            <div class="relative">
                                <i class="fa-solid fa-lock pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-500/70 text-sm"></i>
                                <input
                                    id="password" :type="show ? 'text' : 'password'" name="password"
                                    required autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="w-full rounded-xl border border-amber-200/80 bg-white/80 py-3 pl-11 pr-11 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-300/30"
                                >
                                <button type="button" @click="show = !show"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-amber-400 hover:text-amber-600 transition">
                                    <i x-show="!show" class="fa-regular fa-eye text-sm"></i>
                                    <i x-show="show" x-cloak class="fa-regular fa-eye-slash text-sm"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Remember -->
                        <label class="flex items-center gap-2.5 text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" name="remember"
                                   class="h-4 w-4 rounded border-amber-300 bg-white text-amber-500 focus:ring-amber-400/40 accent-amber-500">
                            Remember me
                        </label>

                        <!-- Submit -->
                        <button type="submit"
                                class="w-full rounded-xl bg-gradient-to-r from-amber-500 via-yellow-400 to-amber-600 py-3 text-sm font-bold text-white shadow-lg shadow-amber-200/70 transition hover:shadow-amber-300/80 hover:scale-[1.01] active:scale-[.99] gold-ring">
                            Sign in
                            <i class="fa-solid fa-arrow-right ml-1.5 text-xs"></i>
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="my-6 flex items-center gap-3">
                        <div class="flex-1 h-px bg-amber-100"></div>
                        <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">or continue with</span>
                        <div class="flex-1 h-px bg-amber-100"></div>
                    </div>

                    <!-- Social login buttons -->
                    <div class="grid grid-cols-3 gap-3">
                        <button type="button" class="flex items-center justify-center gap-2 rounded-xl border border-amber-200/80 bg-white/80 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-amber-50 hover:border-amber-300 transition">
                            <i class="fa-brands fa-google text-amber-600"></i>
                            <span class="hidden sm:inline">Google</span>
                        </button>
                        <button type="button" class="flex items-center justify-center gap-2 rounded-xl border border-amber-200/80 bg-white/80 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-amber-50 hover:border-amber-300 transition">
                            <i class="fa-brands fa-apple text-gray-800"></i>
                            <span class="hidden sm:inline">Apple</span>
                        </button>
                        <button type="button" class="flex items-center justify-center gap-2 rounded-xl border border-amber-200/80 bg-white/80 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-amber-50 hover:border-amber-300 transition">
                            <i class="fa-brands fa-discord text-indigo-500"></i>
                            <span class="hidden sm:inline">Discord</span>
                        </button>
                    </div>

                    <!-- Footer link -->
                    <p class="mt-8 text-center text-sm text-gray-500">
                        New here?
                        <a href="{{ route('register') }}" class="font-semibold text-amber-600 hover:text-amber-700 transition">Create an account</a>
                    </p>
                </div>
            </div>

            <!-- Trust note -->
            <p class="mt-6 text-center text-xs text-gray-400">
                <i class="fa-solid fa-shield-halved text-amber-400 mr-1"></i>
                Protected by end‑to‑end encryption. No spam, ever.
            </p>
        </div>
    </main>

     
</body>
</html>