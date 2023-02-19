$( document ).ready(function( $ ) {

    $('#declareMissingProduct').on('click', function(e) {
        let el = $(this);
        let totalAmount = parseFloat($('#totalAmounts').val());
        if (el.data('isvalid')) {
            return true;
        }
        if (totalAmount <= 0) {
            return true;
        }

        if (!ever_order_ajaxUrl) {
            return;
        }
        var url = ever_order_ajaxUrl;

        e.preventDefault();
        let form = $(this).closest('form');

        let formData = form.serializeArray();
        formData.push({ name: "action", value: "verifMissingProduct" });
        formData.push({ name: "ajax", value: 1 });
        formData.push({ name: "totalAmount", value: totalAmount });

         $.ajax({
            type: 'POST',
            cache: false,
            dataType: 'json',
            url: url,
            data: formData,
            success: function (data) {
                console.log(data);
                $('.inderwear-loading').remove();
                if (!data.has_discounts) {
                    el.data('isvalid', 'true');
                    el.click();
                } else {
                    $.fancybox(data.template, {
                        autoSize: true,
                        minHeight: 10,
                        minWidth: 10,
                        afterShow: function() {
                            $('#submitDiscountForm').click(function() {
                                console.log(el.data('isvalid'));
                                $('input', '#discounts-popin-form').each(function(i, e) {
                                    $(this).attr('type', 'hidden').appendTo('#submitFormProducts');
                                    $(this).attr('type', 'hidden').appendTo('#manquants form');
                                })
                                $.fancybox.close();
                                el.data('isvalid', 'true');
                                el.click();
                            });
                        }
                    });
                }
            },
        });


        // Old script
        // $.ajax({
        //     url: $('#manage_orders_verification_link').data('link'),
        //     type: 'POST',
        //     data: 'totalAmount=' + totalAmount + '&' + form.serialize(),
        //     dataType: 'json',
        //     beforeSend: function() {
        //         // $('body').append('<div class="inderwear-loading"></div>');
        //     },
        //     success: function(response) {
        //         $('.inderwear-loading').remove();
        //         if (!response.has_discounts) {
        //             el.data('isvalid', 'true');
        //             el.click();
        //         } else {
        //             $.fancybox(response.template, {
        //                 autoSize: true,
        //                 minHeight: 10,
        //                 minWidth: 10,
        //                 afterShow: function() {
        //                     $('#submitDiscountForm').click(function() {
        //                         console.log(el.data('isvalid'));
        //                         $('input', '#discounts-popin-form').each(function(i, e) {
        //                             $(this).attr('type', 'hidden').appendTo('#submitFormProducts');
        //                             $(this).attr('type', 'hidden').appendTo('#manquants form');
        //                         })
        //                         $.fancybox.close();
        //                         el.data('isvalid', 'true');
        //                         el.click();
        //                     });
        //                 }
        //             });
        //         }
        //     }
        // });

        return false;
    });
});