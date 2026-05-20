<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Caisse - Menuiserie ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        window.authUser = {!! json_encode(auth()->user()) !!} || null;
        window.appSettings = {!! json_encode(\App\Models\Setting::first() ?? ['company_name' => 'TAAOUATI DESIGN', 'company_phone' => '+212 666-035411 / +212 610-182585', 'invoice_footer_text' => 'Merci pour votre confiance !']) !!};
        window.csrfToken = '{{ csrf_token() }}';
    </script>
</head>
<body class="bg-gray-100 antialiased">
    <div id="app">
        <pos-app></pos-app>
    </div>
</body>
</html>
