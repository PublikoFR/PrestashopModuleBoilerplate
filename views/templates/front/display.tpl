{*
 * Front Template - Display
 *
 * INSTRUCTIONS:
 * 1. Renommer ce fichier selon votre besoin
 * 2. Adapter le layout parent (layout-customer.tpl, layout-both-columns.tpl, etc.)
 * 3. Personnaliser le contenu
 *
 * Variables disponibles:
 * - $module_name : Nom du module
 * - $page_title : Titre de la page
 * - Ajouter vos propres variables dans le controller
 *
 * @author    Publiko
 * @copyright Publiko
 * @license   Commercial
 *}

{extends file='page.tpl'}

{block name='page_title'}
    {$page_title|escape:'htmlall':'UTF-8'}
{/block}

{block name='page_content'}
    <div class="module-boilerplate-content">
        <h2>{l s='Bienvenue' mod='publiko_module_boilerplate'}</h2>

        <p>{l s='Ceci est un exemple de page front-office.' mod='publiko_module_boilerplate'}</p>

        {* Exemple d'affichage d'items *}
        {if isset($items) && $items|count > 0}
            <div class="items-list">
                {foreach from=$items item=item}
                    <div class="item">
                        <h3>{$item.name|escape:'htmlall':'UTF-8'}</h3>
                        {if $item.description}
                            <p>{$item.description nofilter}</p>
                        {/if}
                    </div>
                {/foreach}
            </div>
        {else}
            <p class="alert alert-info">{l s='Aucun élément à afficher.' mod='publiko_module_boilerplate'}</p>
        {/if}
    </div>
{/block}
