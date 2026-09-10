/**
 * Petit client fetch pour l'API JSON de l'assistant (voir
 * routes/web.php "assistant.*" et AssistantController). L'application
 * n'a pas de dependance axios (Inertia gere son propre client interne,
 * non reutilisable pour de simples appels JSON en arriere-plan) - plutot
 * que d'ajouter une dependance seulement pour ce widget, on relit
 * nous-memes le cookie XSRF-TOKEN que Laravel pose deja sur chaque
 * reponse (meme mecanisme que celui qu'Inertia utilise en interne pour
 * ses propres requetes).
 */
function csrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : null;
}

async function parseJsonSafely(response) {
    try {
        return await response.json();
    } catch {
        return null;
    }
}

export async function assistantFetch(url, { method = 'GET', body = null, isForm = false } = {}) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': csrfToken(),
    };

    if (body && !isForm) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers,
        body: body ? (isForm ? body : JSON.stringify(body)) : undefined,
    });

    const data = await parseJsonSafely(response);

    if (!response.ok) {
        const message = data?.error || data?.message || `Erreur ${response.status}`;
        const error = new Error(message);
        error.status = response.status;
        error.data = data;
        throw error;
    }

    return data;
}
