<?php

class CocktailData {
	# Cache time
	public $cache_time = 60 * 60 * 2;
	# Database class
	private $db = [];
	
	
	# Set configs
	public function __construct ($db) {
		if (is_a($db, 'Database') && $db->configs['database']['status']) $this->db = $db;
	}
	
	# Get cocktail info
	public function getCocktail ($id) {
		return $this->db->query('SELECT * FROM cocktails WHERE id = ?', [$id], 1);
	}
	
	# Search cocktail by name
	public function searchCocktail ($name) {
		return $this->db->query('SELECT * FROM cocktails WHERE name ILIKE ?', ['%' . $name . '%'], 2);
	}
	
	# Get ingredient info
	public function getIngredient ($id) {
		return $this->db->query('SELECT * FROM ingredients WHERE id = ?', [$id], 1);
	}
	
}