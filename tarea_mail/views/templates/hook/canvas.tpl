{block name='cupon'}
<div id="tarea_mail" class="container">
    <div class="py-5 text-center zonacupon">
         <h2>{l s='Rasca con un 20 % de descuento' mod='tarea_mail'}</h2>
         <div class="base">{$cupon|escape:'html':'UTF-8'}</div>
         <canvas id="scratch" width="150" height="150"></canvas>
      </div>
</div>
{/block}
