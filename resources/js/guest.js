/**
 * Entry for login, register, and apply (guest) pages.
 * These routes use native HTML forms only — keep this file free of heavy imports
 * so first load stays fast (see resources/js/app.js for the full portal bundle).
 */
import { initWffFormLoading } from './wff-form-loading';

document.addEventListener('DOMContentLoaded', () => {
    initWffFormLoading();
});
