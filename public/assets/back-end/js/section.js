"use strict";

let getYesWord = $('#message-yes-word').data('text');
let getNoWord = $('#message-no-word').data('text');
let messageAreYouSureDeleteThis = $('#message-are-you-sure-delete-this').data('text');
let messageYouWillNotAbleRevertThis = $('#message-you-will-not-be-able-to-revert-this').data('text');



$(".js-example-theme-single").select2({theme: "classic"});
$(".js-example-responsive").select2({width: 'resolve'});

$('#main-banner-add').on('click', function () {
    $('#main-banner').slideToggle();
});

$('.cancel').on('click', function () {
    $('.banner_form').attr('action', $('#route-admin-banner-store').data('url'));
    $('#main-banner').slideToggle();
});




$('.section-delete-button').on('click', function () {
    let sectionId = $(this).attr("id");
    Swal.fire({
        title: messageAreYouSureDeleteThis,
        text: messageYouWillNotAbleRevertThis,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: getYesWord,
        cancelButtonText: getNoWord,
        type: 'warning',
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                }
            });
            $.ajax({
                url: $('#route-admin-section-delete').data('url'),
                method: 'POST',
                data: {id: sectionId},
                success: function (response) {
                    toastr.success(response.message);
                    location.reload();
                }
            });
        }
    })
});

var backgroundImage = $("[data-bg-img]");
backgroundImage.css("background-image", function () {
    return 'url("' + $(this).data("bg-img") + '")';
}).removeAttr("data-bg-img").addClass("bg-img");

$('.most-demanded-product-delete-button').on('click', function () {
    let productId = $(this).attr("id");
    Swal.fire({
        title: $(this).data('warning-text'),
        text:  $(this).data('text'),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        type: 'warning',
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                }
            });
            $.ajax({
                url: $('#route-admin-most-demanded-delete').data('url'),
                method: 'POST',
                data: {id: productId},
                success: function (response) {
                    toastr.success(response.message);
                    location.reload()
                }
            });
        }
    })
})

$('.most-demanded-status-form').on('submit', function(event){
    event.preventDefault();
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });
    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: $(this).serialize(),
        success: function (response) {
            toastr.success(response.message);
            setTimeout(function (){
                location.reload()
            },1000);
        }
    });
});
