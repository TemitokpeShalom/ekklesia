<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fichier trop volumineux</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #0b1220; color: #1c1c1c; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .card { max-width: 26rem; margin: 1.5rem; padding: 2.5rem 2rem; background: #ffffff; border-radius: 1.5rem; box-shadow: 0 20px 50px rgba(0,0,0,0.35); text-align: center; }
        .icon { display: inline-flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: 9999px; background: rgba(0,48,128,0.1); color: #003080; margin-bottom: 1rem; }
        h1 { font-size: 1.25rem; margin: 0 0 0.75rem; color: #1c1c1c; }
        p { font-size: 0.925rem; line-height: 1.5; color: #4b4b4b; margin: 0 0 1.5rem; }
        a.retour { display: inline-flex; align-items: center; gap: 0.4rem; background: linear-gradient(to right, #0a4aa0, #001c50); color: #fff; text-decoration: none; font-weight: 600; font-size: 0.875rem; padding: 0.65rem 1.4rem; border-radius: 0.75rem; }
    </style>
</head>
<body>
    <div class="card">
        <span class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
        </span>
        <h1>Fichier trop volumineux pour le serveur</h1>
        <p>
            Le fichier joint dépasse la taille actuellement acceptée par le serveur.
            Réessayez en revenant en arrière avec un fichier plus petit (photo compressée,
            document scanné en plus basse résolution...), ou signalez-le à l'administrateur
            de la plateforme pour augmenter cette limite.
        </p>
        <a class="retour" href="javascript:history.back()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Retour
        </a>
    </div>
</body>
</html>
