{*
* Project : everpstools
* @author Celaneo
* @copyright Celaneo
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.celaneo.com/
*}
<div id="discounts-popin-form" class="bootstrap" style="width: 400px">

    <p>{l s='Modify the amount of the voucher if necessary' mod='everpstools'} :</p>

    {foreach from=$discounts item=discount}
        <div class="form-group">
            <label for="" style="margin-bottom: 10px">{$discount.code}</label>
            <div class="row" style="margin: 0; margin-bottom: 10px">
                <div class="col-lg-1" style="padding-top: 5px">TTC</div>
                <div class="col-lg-3">
                    <input type="text" class="form-control" name="discounts[{$discount.id_cart_rule}][tax_incl]" value="{$discount.calculated_tax_incl}" data-tva="{$discount.tva}" />
                </div>
                <div class="col-lg-1"></div>
                <div class="col-lg-1" style="padding-top: 5px">HT</div>
                <div class="col-lg-3">
                    <input type="text" class="form-control" name="discounts[{$discount.id_cart_rule}][tax_excl]" value="{$discount.calculated_tax_excl}" data-tva="{$discount.tva}" />
                </div>
            </div>
        </div>
        <p>{l s='New total products excluding promotions' mod='everpstools'} : {$amount_after}€</p>
        <hr />
    {/foreach}

    <button id="submitDiscountForm" class="btn btn-primary">{l s='Validate' mod='everpstools'}</button>
</div>

<script type="text/javascript">

    $('input[name="discounts[{$discount.id_cart_rule}][tax_incl]"]').on('keyup', function() {
        $('input[name="discounts[{$discount.id_cart_rule}][tax_excl]"]').val($(this).val() / (1 + $(this).data('tva') / 100));
    });

</script>