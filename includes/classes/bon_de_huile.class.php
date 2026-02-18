<?php
class bon_de_huile {
    private $id;
    private $quantite;
    private $date_bon;
    private $prix_unitaire;
    private $prix_net;
    private $provider;
    private $note;
    private $created_at;

    // ID
    public function getId() {
        return $this->id;
    }
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    // Quantité
    public function getQuantite() {
        return $this->quantite;
    }
    public function setQuantite($quantite) {
        $this->quantite = $quantite;
        return $this;
    }

    // Date Bon
    public function getDateBon() {
        return $this->date_bon;
    }
    public function setDateBon($date_bon) {
        $this->date_bon = $date_bon;
        return $this;
    }

    // Prix Unitaire
    public function getPrixUnitaire() {
        return $this->prix_unitaire;
    }
    public function setPrixUnitaire($prix_unitaire) {
        $this->prix_unitaire = $prix_unitaire;
        return $this;
    }

    // Prix Net
    public function getPrixNet() {
        return $this->prix_net;
    }
    public function setPrixNet($prix_net) {
        $this->prix_net = $prix_net;
        return $this;
    }

    // Provider
    public function getProvider() {
        return $this->provider;
    }
    public function setProvider($provider) {
        $this->provider = $provider;
        return $this;
    }

    // Note
    public function getNote() {
        return $this->note;
    }
    public function setNote($note) {
        $this->note = $note;
        return $this;
    }

    // Created At
    public function getCreatedAt() {
        return $this->created_at;
    }
    public function setCreatedAt($created_at) {
        $this->created_at = $created_at;
        return $this;
    }
}

?>









