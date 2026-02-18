<?php

class Client {
    private $id;
    private $nom;
    private $prenom;
    private $phone;
    private $adress;
    private $ville;
    private $created_at;

    // ID
    public function getId() {
        return $this->id;
    }
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    // Nom
    public function getNom() {
        return $this->nom;
    }
    public function setNom($nom) {
        $this->nom = $nom;
        return $this;
    }

    // Prenom
    public function getPrenom() {
        return $this->prenom;
    }
    public function setPrenom($prenom) {
        $this->prenom = $prenom;
        return $this;
    }

    // Phone
    public function getPhone() {
        return $this->phone;
    }
    public function setPhone($phone) {
        $this->phone = $phone;
        return $this;
    }

    // Adress
    public function getAdress() {
        return $this->adress;
    }
    public function setAdress($adress) {
        $this->adress = $adress;
        return $this;
    }

    // Ville
    public function getVille() {
        return $this->ville;
    }
    public function setVille($ville) {
        $this->ville = $ville;
        return $this;
    }

    // Created_at
    public function getCreatedAt() {
        return $this->created_at;
    }
    public function setCreatedAt($created_at) {
        $this->created_at = $created_at;
        return $this;
    }
}
