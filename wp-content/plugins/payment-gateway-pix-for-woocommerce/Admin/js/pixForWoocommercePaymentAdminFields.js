jQuery(document).ready(function ($) {
    $('.admin-gateway-title-link').on('click', function (e) {
        e.preventDefault();
        var target = $(this).data('target');
        $('.admin-gateway-title-link').removeClass('active');
        $(this).addClass('active');
        $('.admin-gateway-block').removeClass('active');
        $('#' + target).addClass('active');
    });
    // Exibe o primeiro bloco por padrão
    $('.admin-gateway-title-link.active').trigger('click');
});