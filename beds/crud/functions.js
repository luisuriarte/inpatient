// Mostrar la ventana emergente
function showWarning(message) {
    const warningMessageElem = document.getElementById('warningMessage');
    const warningPopupElem = document.getElementById('warningPopup');

    if (warningMessageElem && warningPopupElem) {
        warningMessageElem.innerText = message;
        warningPopupElem.style.display = 'block';
    } else {
        console.error('No se encontraron los elementos del DOM.');
    }
}

// Cerrar la ventana emergente
function closeWarning(redirectUrl) {
    const warningPopupElem = document.getElementById('warningPopup');
    if (warningPopupElem) {
        warningPopupElem.style.display = 'none';
        if (redirectUrl) {
            window.location.href = redirectUrl;  // Redirigir a la URL especificada
        }
    }
}