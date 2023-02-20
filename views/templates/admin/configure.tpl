{*
* Project : everpstools
* @author Team Ever
* @copyright Team Ever
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.celaneo.com/
*}
<div class="alert alert-info row">
    <h4>{l s='Documentation' mod='everpstools'}</h4>
    <br>
    {if isset($ever_crons) && $ever_crons}
    <h3>{l s='Crons' mod='everpstools'}</h3>
    <ul>
        {foreach from=$ever_crons item=cron}
        <li><code>{$cron.link|escape:'htmlall':'UTF-8'}</code> {$cron.description|escape:'htmlall':'UTF-8'}</li>
        {/foreach}
    </ul>
    {/if}
    <br>
    <br>
    <h3>{l s='How to include Ever Tools on your own module ?' mod='everpstools'}</h3>
    <p>{l s='Add this code on the highest level of your module' mod='everpstools'}</p>
    <p>
        <pre>
            if (Module::isInstalled('everpstools')) {
                $ever = Module::getInstanceByName('everpstools');
                $ever->getEverConfigurationObjects();
            }
        </pre>
    </p>
</div>