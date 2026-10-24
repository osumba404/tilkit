const SQL_HINTS = ['SQLSTATE[', 'SQL:', 'Connection:', 'Integrity constraint violation'];

function looksLikeSql(message) {
    if (typeof message !== 'string' || message === '') {
        return false;
    }
    return SQL_HINTS.some((hint) => message.includes(hint));
}

/** @param {Record<string, unknown>} json */
export function messageFromApiResponse(json, fallback = 'Something went wrong. Please try again.') {
    if (json && typeof json.message === 'string' && json.message !== '' && !looksLikeSql(json.message)) {
        return json.message;
    }

    const errors = json?.errors;
    if (errors && typeof errors === 'object') {
        for (const key of Object.keys(errors)) {
            const entry = errors[key];
            if (Array.isArray(entry) && typeof entry[0] === 'string' && entry[0] !== '') {
                return entry[0];
            }
            if (typeof entry === 'string' && entry !== '') {
                return entry;
            }
        }
    }

    return fallback;
}
