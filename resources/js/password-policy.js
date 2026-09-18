// Client-side mirror of App\Support\PasswordPolicy (single source of truth
// for the "8+ chars, letters, numbers, symbol" rule). Keep both in sync.
export const PASSWORD_MIN_LENGTH = 8;

const HAS_LETTER = /[A-Za-z]/;
const HAS_NUMBER = /[0-9]/;
const HAS_SYMBOL = /[^A-Za-z0-9]/;

export const PasswordPolicy = {
    minLength: PASSWORD_MIN_LENGTH,
    message: `Password must be at least ${PASSWORD_MIN_LENGTH} characters and include a letter, a number, and a symbol.`,
    invalid(password) {
        return (
            password.length < PASSWORD_MIN_LENGTH
            || ! HAS_LETTER.test(password)
            || ! HAS_NUMBER.test(password)
            || ! HAS_SYMBOL.test(password)
        );
    },
};
