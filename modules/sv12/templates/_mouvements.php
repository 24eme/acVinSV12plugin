<?php use_helper('Float'); use_helper('Date'); ?>
<fieldset id="mouvement_sv12">
        <table class="table_recap">
        <thead>
        <tr>
            <th>Date de modification</th>
            <th>Contrat</th>
            <th>Produit</th>
            <th>Volume</th>

        </tr>
        </thead>
        <tbody>
            <?php $i = 1; ?>
            <?php foreach ($mouvements as $mouvement) :
            ?>   

            <tr <?php if($i%2!=0) echo ($mouvement->volume > 0)? ' class="alt"' : 'class="alt"';  ?>>
                <td><?php echo sprintf("%s - %s", ($mouvement->version) ? $mouvement->version : 'M00', format_date($mouvement->date_version));?></td>
                <td>
                	<?php if (!$mouvement->vrac_numero): ?>
                	-
                	<?php else: ?>
                    <a href="<?php echo url_for(array('sf_route' => 'vrac_visualisation', 'numero_contrat' => $mouvement->vrac_numero)) ?>"><?php echo VracClient::getInstance()->getLibelleFromId($mouvement->vrac_numero, '&nbsp;') ?></a> <?php echo sprintf("(%s, %s)", $mouvement->type_libelle, $mouvement->vrac_destinataire); ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php echo $mouvement->produit_libelle; ?>
                </td>
                <td <?php echo ($mouvement->volume > 0)? ' class="positif"' : 'class="negatif"';?> >
                    <?php  echoSignedFloat($mouvement->volume); ?>
                </td>
            </tr>
            <?php
            endforeach;
            ?>
        </tbody>
        </table> 
</fieldset>