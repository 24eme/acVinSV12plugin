<?php

class sv12Actions extends sfActions {

    public function executeChooseEtablissement(sfWebRequest $request) {
        $this->form = new SV12EtablissementChoiceForm('INTERPRO-inter-loire');

        $this->historySv12 = array();
        
        if ($request->isMethod(sfWebRequest::POST)) {
            $this->form->bind($request->getParameter($this->form->getName()));
            if ($this->form->isValid()) {
                return $this->redirect('sv12_etablissement', $this->form->getEtablissement());
            }
        }
    }

    public function executeMonEspace(sfWebRequest $request) {
        $this->etablissement = $this->getRoute()->getEtablissement();
	if (!$this->etablissement->isNegociant())
	  throw new sfException('Seuls les négociants peuvent faire des SV12');
        $this->periode = SV12Client::getInstance()->buildPeriode(date('Y-m-d'));
        $this->list = SV12AllView::getInstance()->getMasterByEtablissement($this->etablissement->identifiant);
    }

    /**
     *
     * @param sfWebRequest $request 
     */
    public function executeNouvelle(sfWebRequest $request) {

        $etbId = $request->getParameter('identifiant');
        $periode = $request->getParameter('periode');
        
        $sv12 = SV12Client::getInstance()->findMaster($etbId,$periode);

        if($sv12)
        {               
            
            return $this->renderPartial('popupWarning',array('sv12' => $sv12));
        }
        
        $sv12 = SV12Client::getInstance()->createOrFind($etbId, $periode);
        $sv12->storeContrats();
        $sv12->save();
        $this->redirect('sv12_update', $sv12);
    }

    public function executeUpdate(sfWebRequest $request) {

        $this->sv12 = $this->getRoute()->getSV12();
        $this->sv12->storeContrats();       
        $this->form = new SV12UpdateForm($this->sv12);

        if ($request->isMethod(sfWebRequest::POST)) {
            $this->form->bind($request->getParameter($this->form->getName()));
            if ($this->form->isValid()) {
                $this->form->doUpdateObject();
                $this->sv12->save();
		if ($request->getParameter('addproduit'))
		  return $this->redirect('sv12_update_addProduit', $this->sv12);
                return $this->redirect('sv12_recapitulatif', $this->sv12);
            }
        }
    }

    public function executeRecapitulatif(sfWebRequest $request) {
        $this->sv12 = $this->getRoute()->getSV12();
	$this->validation = new SV12Validation($this->sv12);
        $this->mouvements = $this->sv12->getMouvementsCalculeByIdentifiant($this->sv12->identifiant);
        $this->sv12->updateTotaux();
        
        if ($request->isMethod(sfWebRequest::POST)) {
            $this->sv12->validate();
            $this->sv12->save();
            $this->redirect('sv12_visualisation', $this->sv12);
        }
    }
    
    public function executeVisualisation(sfWebRequest $request) {
        $this->sv12 = $this->getRoute()->getSV12();
        $this->contrats_non_saisis = $this->sv12->getContratsNonSaisis();
        $this->mouvements = SV12MouvementsConsultationView::getInstance()->getMouvementsByEtablissementAndPeriode($this->sv12->identifiant, $this->sv12->periode); 
    }

    public function executeModificative(sfWebRequest $request)
    {
        $sv12 = $this->getRoute()->getSV12();

        $sv12_rectificative = $sv12->generateModificative();
        $sv12_rectificative->save();

        return $this->redirect('sv12_update', array('identifiant' => $sv12_rectificative->identifiant, 'periode_version' => $sv12_rectificative->getPeriodeAndVersion()));
    }
    
    private function saveBrouillonSV12() {
        $this->sv12->saveBrouillon();
    }
    
    public function executeUpdateAddProduit(sfWebRequest $request)
    {
        $this->sv12 = $this->getRoute()->getSV12();
        $this->form = new SV12UpdateAddProduitForm($this->sv12);
         if ($request->isMethod(sfWebRequest::POST)) {
             $this->form->bind($request->getParameter($this->form->getName()));
            if ($this->form->isValid()) {
                $produit = $this->form->addProduit();
                $this->sv12->save();
                return $this->redirect('sv12_update', $this->sv12);
            }
       }
    }
}
