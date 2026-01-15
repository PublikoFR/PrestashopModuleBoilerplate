{*
 * Hook Template - displayHome
 *
 * INSTRUCTIONS:
 * 1. Renommer ce fichier selon le hook utilisé (displayHome.tpl, displayLeftColumn.tpl, etc.)
 * 2. Adapter le contenu selon vos besoins
 *
 * Variables disponibles:
 * - $items : Liste des items (si passé depuis le hook)
 * - Ajouter vos propres variables dans le module (hookDisplayHome)
 *
 * @author    Publiko
 * @copyright Publiko
 * @license   Commercial
 *}

<div class="module-boilerplate-hook">
    <h3>{l s='Titre du bloc' mod='publiko_module_boilerplate'}</h3>

    {if isset($items) && $items|count > 0}
        <ul class="boilerplate-items">
            {foreach from=$items item=item}
                <li>
                    <strong>{$item.name|escape:'htmlall':'UTF-8'}</strong>
                </li>
            {/foreach}
        </ul>
    {else}
        <p>{l s='Aucun élément.' mod='publiko_module_boilerplate'}</p>
    {/if}
</div>
