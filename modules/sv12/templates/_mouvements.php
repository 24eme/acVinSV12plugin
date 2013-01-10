<?php use_helper('Float'); ?> 
<?php use_helper('Date'); ?>
<?php use_helper('Mouvement') ?>

<?php if (count($mouvements) > 0): ?>

    <?php if (isset($hamza_style)) : ?>
        <?php
        include_partial('global/hamzaStyle', array('table_selector' => '#table_mouvements',
            'mots' => mouvement_get_words($mouvements),
            'consigne' => "Saisissez un produit, un numéro de contrat, un viticulteur ou un type (moût / raisin / vrac) :"))
        ?>
    <?php endif; ?>
    <fieldset id="table_mouvements">
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
                <tr id="<?php echo mouvement_get_id($mouvement) ?>" class="<?php if ($i % 2 != 0) echo 'alt'; if ($mouvement->facturable) echo " facturable"; ?>">
                        <td>
                            <?php $sv12_libelle = sprintf("%s&nbsp;%s %s", $mouvement->type, ($mouvement->version) ? '(' . $mouvement->version . ')' : '', format_date($mouvement->date_version));
                                echo link_to2($sv12_libelle,'redirect_visualisation', array('id_doc' => $mouvement->doc_id)); 
                            ?></td>
                        <td>
                            <?php if ($mouvement->vrac_numero) { ?>
                                <a href="<?php echo url_for(array('sf_route' => 'vrac_visualisation', 'numero_contrat' => $mouvement->vrac_numero)) ?>"><?php echo VracClient::getInstance()->getLibelleFromId($mouvement->vrac_numero, '&nbsp;') ?></a> <?php echo sprintf("(%s, %s)", $mouvement->type_libelle, $mouvement->vrac_destinataire); ?>
                            <?php
                            } else if ($mouvement->vrac_destinataire) {
                                echo "SANS CONTRAT " . sprintf("(%s, %s)", $mouvement->type_libelle, $mouvement->vrac_destinataire);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                    <?php echo $mouvement->produit_libelle; ?>
                        </td>
                        <td <?php echo ($mouvement->volume > 0) ? ' class="positif"' : 'class="negatif"'; ?> >
        <?php echoSignedFloat($mouvement->volume * -1); ?>
                        </td>
                    </tr>
        <?php
    endforeach;
    ?>
            </tbody>
        </table> 
    </fieldset>
<?php else: ?>
    <p>Pas de mouvements</p>
<?php endif; ?>