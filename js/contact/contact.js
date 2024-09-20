const textarea = document.getElementById('mensagem');
const charCount = document.getElementById('charCount');
const maxLength = textarea.getAttribute('maxlength');

function updateCharCount() {
    const currentLength = textarea.value.length;
    const remaining = maxLength - currentLength;
    if (currentLength > 0) {
        charCount.textContent = `${remaining} caracteres restantes`;
        charCount.style.display = 'block';
    } else {
        charCount.style.display = 'none';
    }
}

textarea.addEventListener('input', updateCharCount);
textarea.addEventListener('focus', updateCharCount);