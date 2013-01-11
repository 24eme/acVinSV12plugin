<?php
/**
 * Model for SV12Contrat
 *
 */

class SV12Contrat extends BaseSV12Contrat {
    protected $vrac = null;

    public function getMouvementVendeur() {
        $mouvement = $this->getMouvement();
        if (!$mouvement) {

            return null;
        }
        $mouvement->vrac_destinataire = $this->getDocument()->declarant->nom;
	$mouvement->region = $this->getVendeur()->region;

        if ($this->getVrac()) {
        	$mouvement->cvo = $this->getTauxCvo() * $this->getVrac()->cvo_repartition * 0.01;
	} else {
        	$mouvement->cvo = $this->getTauxCvo() * 0.5;
        }

        return $mouvement;
    }

    public function getVendeur() {
      return EtablissementClient::getInstance()->find($this->vendeur_identifiant);
    }

    public function getAcheteur() {
      return $this->getDocument()->getEtablissementObject();
    }


    public function getMouvementAcheteur() {
        $mouvement = $this->getMouvement();
        if (!$mouvement) {
            
            return null;
        }

        $mouvement->vrac_destinataire = $this->vendeur_nom;
	$mouvement->region = $this->getAcheteur()->region;
        if ($this->getVrac()) {
            $mouvement->cvo = $this->getTauxCvo() * $this->getVrac()->cvo_repartition * 0.01;
        } else if ($this->vendeur_identifiant) {
	  $mouvement->cvo = $this->getTauxCvo() * 0.5;	  
	} else {
	  $mouvement->cvo = $this->getTauxCvo();	  
	}

        return $mouvement;
    }

    protected function getVolumeVersion() {
        if ($this->getDocument()->hasVersion() && !$this->getDocument()->isModifiedMother($this, 'volume')) {

            return 0;
        }

        $volume = $this->volume;

        if($this->getDocument()->hasVersion() && $this->getDocument()->motherExist($this->getHash().'/volume')) {
            $volume = $volume - $this->getDocument()->motherGet($this->getHash().'/volume');
        }

        return $volume;
    }

    protected function getMouvement() {

        $volume = $this->getVolumeVersion();

        if($volume == 0) {
            return null;
        }

        $mouvement = DRMMouvement::freeInstance($this->getDocument());
        $mouvement->produit_hash = $this->produit_hash;
        $mouvement->facture = 0;
        $mouvement->version = $this->getDocument()->version;
        $mouvement->date_version = ($this->getDocument()->valide->date_saisie) ? ($this->getDocument()->valide->date_saisie) : date('Y-m-d');
        if ($this->contrat_type == VracClient::TYPE_TRANSACTION_RAISINS) {
            $mouvement->categorie = FactureClient::FACTURE_LIGNE_PRODUIT_TYPE_RAISINS;  
        } elseif($this->contrat_type == VracClient::TYPE_TRANSACTION_MOUTS) {
            $mouvement->categorie = FactureClient::FACTURE_LIGNE_PRODUIT_TYPE_MOUTS;  
        }
        if (!$this->getVrac())
        	$mouvement->categorie = FactureClient::FACTURE_LIGNE_PRODUIT_TYPE_ECART;  
        $mouvement->type_hash = $this->contrat_type;
        $mouvement->type_libelle = sprintf("Contrat %s", strtolower($this->getContratTypeLibelle()));
        $mouvement->volume = -1 * $volume;
	    $mouvement->facturable = 1;
        $mouvement->date = $this->getDocument()->getDate();
        $mouvement->vrac_numero = $this->contrat_numero;
        if ($this->getVrac())
        	$mouvement->detail_identifiant = $this->getVracIdentifiant();
        else 
        	$mouvement->detail_identifiant = null;
        $mouvement->detail_libelle = $this->contrat_numero;

        return $mouvement;
    }
    
    public function canBeSoldable() {
        
        return $this->isSaisi();
    }

    public function isSaisi() {
        return !is_null($this->volume); 
    }

    public function isSansContrat() {

        return is_null($this->contrat_numero);
    }

    public function enleverVolume() {
        if ($this->isSansContrat()) {

            return false;
        }

        $volume = $this->getVolumeVersion();

		if (!$this->getVrac()) {

            throw new sfException(sprintf("Le contrat %s est introuvable", $this->getVracIdentifiant()));            
        }

        if ($this->isSaisi() && $volume == 0 && $this->getVrac()->isSolde()) {

            return false;
        }

        $this->getVrac()->enleverVolume($this->getVolumeVersion());

        if ($this->canBeSoldable()) {
            $this->getVrac()->solder();
        } else {
            $this->getVrac()->desolder();
        }

        return true;
    }

    public function getVrac() {
        if (is_null($this->vrac)) {
            $this->vrac = VracClient::getInstance()->find($this->getVracIdentifiant());
        }

        return $this->vrac;
    }

    public function getVracIdentifiant() {

        return 'VRAC-'.$this->contrat_numero;
    }

    public function getDroitCVO() {
        
        return $this->getProduitObject()->getDroitCVO($this->getDocument()->getDate());
    }

    public function getTauxCvo() {
        if(is_null($this->cvo)) {
            $this->cvo = $this->getDroitCVO()->taux;
        }

        return $this->cvo;
    }

    public function getProduitObject() 
    {

        return ConfigurationClient::getCurrent()->get($this->produit_hash);
    }

    public function getContratTypeLibelle() {

        return VracClient::$types_transaction[$this->contrat_type]; 
    }

    function getNumeroArchive() {
      return VracClient::getInstance()->findByNumContrat($this->contrat_numero)->numero_archive;
    }

    function updateNoContrat($produit, $infoviti = array('contrat_type' => null, 'vendeur_identifiant' => null, 'vendeur_nom' => null))
    {
      if ($this->volume)
	return ;
      $this->contrat_numero = null;
      $this->contrat_type = $infoviti['contrat_type'];
      $this->produit_libelle = $produit->getLibelleFormat(array(), "%g% %a% %m% %l% %co% %ce% %la%");
      $this->produit_hash = $produit->getHash();
      $this->vendeur_identifiant = $infoviti['vendeur_identifiant'];
      $this->vendeur_nom = $infoviti['vendeur_nom'];
      $this->volume_prop = null;
      $this->volume = null;
    }

}