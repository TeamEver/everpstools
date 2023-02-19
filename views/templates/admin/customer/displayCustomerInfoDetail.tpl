{*
* Project : everpstools
* @author Celaneo
* @copyright Celaneo
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.celaneo.com/
*}
{if isset($ever_informations) && $ever_informations}
{foreach from=$ever_informations key=ever_infoname item=ever_infovalue}
<div class="row">
    <label class="control-label col-lg-3">{$ever_infoname|escape:'htmlall':'UTF-8'}</label>
    <div class="col-lg-9">
        <p class="form-control-static">
            {$ever_infovalue|escape:'htmlall':'UTF-8'}
        </p>
    </div>
</div>
{/foreach}
{/if}
