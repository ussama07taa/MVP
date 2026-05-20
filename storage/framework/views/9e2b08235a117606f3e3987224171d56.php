<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - SaaS Menuiserie</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased selection:bg-indigo-500 selection:text-white">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        
        <!-- Logo / Branding -->
        <div class="mb-8 text-center">
            <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-indigo-500/30">
                <i class="fa-solid fa-layer-group text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">SaaS Menuiserie</h1>
            <p class="text-sm text-slate-500 font-medium mt-1">Gestion d'Atelier & ERP Point de Vente</p>
        </div>

        <!-- Login Card -->
        <div class="w-full sm:max-w-md bg-white px-8 py-10 shadow-xl shadow-slate-200/50 rounded-[2rem] border border-slate-100">
            <h2 class="text-xl font-bold text-slate-800 mb-6">Bon retour parmi nous 👋</h2>

            <!-- Validation Errors -->
            <?php if($errors->any()): ?>
                <div class="mb-6 p-4 bg-red-50 rounded-xl border border-red-100">
                    <div class="font-medium text-red-600 text-sm">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> Oups! Une erreur s'est produite.
                    </div>
                    <ul class="mt-2 list-disc list-inside text-xs text-red-500">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('login')); ?>">
                <?php echo csrf_field(); ?>

                <!-- Email Address -->
                <div class="mb-5">
                    <label for="email" class="block text-sm font-bold text-slate-700 mb-2">Adresse Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-regular fa-envelope text-slate-400"></i>
                        </div>
                        <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus autocomplete="username" 
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" 
                            placeholder="admin@erp.com">
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <label for="password" class="block text-sm font-bold text-slate-700">Mot de Passe</label>
                        <?php if(Route::has('password.request')): ?>
                            <a class="text-xs font-bold text-indigo-600 hover:text-indigo-500" href="<?php echo e(route('password.request')); ?>">
                                Oublié ?
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-lock text-slate-400"></i>
                        </div>
                        <input id="password" type="password" name="password" required autocomplete="current-password" 
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" 
                            placeholder="••••••••">
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="block mb-6">
                    <label for="remember_me" class="inline-flex items-center cursor-pointer">
                        <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                        <span class="ml-2 text-sm text-slate-600 font-medium">Se souvenir de moi</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-black text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors active:scale-[0.98]">
                        <i class="fa-solid fa-right-to-bracket mr-2"></i> SE CONNECTER
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="mt-8 text-center text-xs text-slate-400 font-medium">
            &copy; 2026 Menuiserie Taaouati ERP. Tous droits réservés.
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\Users\Taaouati\Documents\SAS_Menu-gitmain\SAS_Menu-main\resources\views/auth/login.blade.php ENDPATH**/ ?>