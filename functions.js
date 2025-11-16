
// Función para configurar autocompletado en un campo de texto
function setupAutocomplete(inputId, modalId, hiddenInputId) {
    $('#' + modalId).on('shown.bs.modal', function () {
        $('#' + inputId).focus();
        
        setTimeout(function() {
            $('#' + inputId).autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: 'https://openemr-domain/inpatient/search_users.php', // Ruta absoluta desde la raíz del dominio
                        dataType: 'json',
                        data: { query: request.term },
                        success: function(data) {
                            var transformedResults = data.results.map(function(user) {
                                return { label: user.textR, value: user.userIdR };
                            });
                            response(transformedResults);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('Error en la solicitud AJAX:', textStatus, errorThrown);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $('#' + inputId).val(ui.item.label);
                    $('#' + hiddenInputId).val(ui.item.value);

                    return false; // Evitar que el valor ID aparezca en el campo de texto
                }
            }).focus(); // Forzar el foco en el campo
        }, 100);
    });
}

// Función para verificar si se ha seleccionado una persona responsable
function isResponsiblePersonSelected(inputId, modalId, formId) {
    var responsibleUser = $('#' + inputId).val();

    if (!responsibleUser) {
        $('#' + modalId).modal('show').on('shown.bs.modal', function() {
            $(this).css('z-index', '1061');
        });
    } else {
        $('#' + formId).submit();
    }
}

$(document).ready(function(){
    // Inicializa los tooltips
    $('[data-toggle="message"]').tooltip();

    // Al hacer clic o recibir el foco (focus) en un input, esconde el tooltip
    $('[data-toggle="message"]').on('focus click', function(){
        $(this).tooltip('hide');
    });
});

function blinkElement(itemId) {
    // Alternar clases de CSS para hacer parpadear el elemento
    var isHighlighted = false;
    setInterval(function() {
        isHighlighted = !isHighlighted;
        items.update({ id: itemId, className: isHighlighted ? 'blinking-event' : 'normal-event' });
    }, 500);  // Cambia de estilo cada 500 ms (0.5 segundos)
}

timeline.on('currentTimeTick', function() {
    var currentTime = new Date().getTime();

    items.forEach(function (item) {
        var startTime = new Date(item.start).getTime();
        var timeDifference = Math.abs(startTime - currentTime);

        if (timeDifference < 1000 * 60) {  // Si el tiempo está cerca del actual
            blinkElement(item.id);  // Inicia el parpadeo
        }
    });
});

function topatient(newpid, enc) {
    top.restoreSession();
    if (enc > 0) {
        top.RTop.location = "<?php echo $GLOBALS['webroot']; ?>/interface/patient_file/summary/demographics.php?set_pid=" + encodeURIComponent(newpid) + "&set_encounterid=" + encodeURIComponent(enc);
    } else {
        top.RTop.location = "<?php echo $GLOBALS['webroot']; ?>/interface/patient_file/summary/demographics.php?set_pid=" + encodeURIComponent(newpid);
    }
}
//
