<?php
/*****************************************************************************************************
 * @name MatriceFields
 * @note: Tool used to process an array of data with respect to a matrix
 * @author: Jgauthi <github.com/jgauthi>, created at [21jun2018]
 * @version 1.0

 ******************************************************************************************************/
namespace Jgauthi\Tools\Matrice;

use DateTime;
use Exception;
use InvalidArgumentException;

class MatriceFields
{
    private $rules;
    private $export_array = null;
    public $check_maxlength = false;
    public $key_case_sensitive = false;

    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Vérifier la compatibilité des champs requis avec les données fournits
     * (pour éviter d'utiliser des données "Hors sujet").
     */
    public function check_titles(array $data)
    {
        $data_title = array_keys($data);
        $rules_title = array_keys($this->rules);

        if (!$this->key_case_sensitive) {
            $rules_title = array_map('strtolower', $rules_title);
            $data_title = array_map('strtolower', $data_title);
        }

        return count(array_intersect($data_title, $rules_title)) === count($data_title);
    }

    /**
     * Vérifie que chaque champ correct aux règles établies dans la matrice.
     */
    public function check_fields(array $data)
    {
        $errors = $export = [];

        if (!$this->key_case_sensitive) {
            $data = array_change_key_case($data, CASE_LOWER);
            $this->rules = array_change_key_case($this->rules, CASE_LOWER);
        }

        foreach ($this->rules as $key => $current_rule) {
            // Ne pas sauvegarder ce champ dans l'export
            if (!isset($data[$key])) {
                continue;
            }

            // Récupération du champ et suppression de la liste
            $value = $data[$key];
            unset($data[$key]);

            // Aucune valeur
            if ($value === null || $value == '') {
                if ($current_rule['required']) {
                    $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) doit être complété.";
                    continue;
                }

                $value = null;
            } else {
                if ($this->check_maxlength && !empty($current_rule['maxlength'])) {
                    $count = mb_strlen($value);
                    if ($count > $current_rule['maxlength']) {
                        $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) dépasse le nombre de caractère authorisé ({$current_rule['maxlength']}).";
                        continue;
                    }
                }

                if (!empty($current_rule['expected_value'])) {
                    if (preg_match('#,#', $current_rule['expected_value'])) {
                        $expected_value = explode(',', $current_rule['expected_value']);
                        if (!preg_grep("#{$value}#i", $expected_value)) {
                            $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) a une valeur incorrecte: {$value}".
                                ', attendue: '.implode(' ou ', $expected_value);

                            if (!empty($current_rule['comment'])) {
                                $errors[$key] .= " ({$current_rule['comment']})";
                            }

                            continue;
                        }
                    } elseif (!preg_match("#{$current_rule['expected_value']}#i", $value)) {
                        $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) a une valeur incorrecte: {$value}";

                        if (!empty($current_rule['comment'])) {
                            $errors[$key] .= ', '.$current_rule['comment'];
                        }

                        continue;
                    }
                }

                // Type de champs
                if (!empty($current_rule['type'])) {
                    switch ($current_rule['type']) {
                        case 'email':
                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) doit être email valide.";
                                continue;
                            }
                            break;

                        case 'int':
                            if (!is_numeric($value)) {
                                $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) doit être nombre valide.";
                                continue;
                            }
                            break;

                        case 'url':
                            //if(!filter_var($value, FILTER_VALIDATE_URL)) // Certains caractères excentrique de econocom ne passait pas.
                            if (!preg_match('#https?://([^/]+)/#', $value)) {
                                $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) doit être une url valide.";
                                continue;
                            }
                            break;

                        case 'date':
                            // Format de date spécifié
                            if (!empty($current_rule['date_format'])) {
                                try {
                                    $value = DateTime::createFromFormat($current_rule['date_format'], $value);
                                } catch (Exception $e) {
                                    $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) doit être une date valide: ".$e->getMessage();
                                    continue;
                                }

                                $value = $value->format('Y-m-d');
                            }
                            // Date US
                            elseif (preg_match("#^([0-9]{4})(\/|-)?([0-1]?[0-9])(\/|-)?([0-3]?[0-9])$#i", $value, $row)) {
                                $value = sprintf('%4d-%02d-%02d', $row[1], $row[3], $row[5]);
                            }

                            // Date FR
                            elseif (preg_match("#^([0-3]?[0-9])(\/|-)?([0-1]?[0-9])(\/|-)?([0-9]{4})$#i", $value, $row)) {
                                $value = sprintf('%4d-%02d-%02d', $row[5], $row[3], $row[1]);
                            }

                            // Date FR (année courte)
                            elseif (preg_match("#^([0-3]?[0-9])(\/|-)?([0-1]?[0-9])(\/|-)?([0-9]{2})$#i", $value, $row)) {
                                $value = sprintf('20%d-%02d-%02d', $row[5], $row[3], $row[1]);
                            }

                            // Date inconnu
                            else {
                                $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) doit être une date valide";
                                if (!empty($current_rule['comment'])) {
                                    $errors[$key] .= " ({$current_rule['comment']})";
                                }

                                $errors[$key] .= '.';
                                continue;
                            }
                            break;

                        case 'datetime':
                            // Date + Heure US
                            if (preg_match("#^([0-9]{4})(\/|-)?([0-1]?[0-9])(\/|-)?([0-3]?[0-9]) ([0-9]{2}):([0-9]{2})(:[0-9]{2})?$#i", $value, $row)) {
                                $value = sprintf('%4d-%02d-%02d %02d:%02d:%02d', $row[1], $row[3], $row[5], $row[6], $row[7], (isset($row[9]) ? $row[9] : 0));
                            }

                            // Date + Heure FR
                            elseif (preg_match("#^([0-3]?[0-9])(\/|-)?([0-1]?[0-9])(\/|-)?([0-9]{4}) ([0-9]{2}):([0-9]{2})(:[0-9]{2})?$#i", $value, $row)) {
                                $value = sprintf('%4d-%02d-%02d %02d:%02d:%02d', $row[5], $row[3], $row[1], $row[6], $row[7], (isset($row[9]) ? $row[9] : 0));
                            }

                            // Date + Heure FR (année courte)
                            elseif (preg_match("#^([0-3]?[0-9])(\/|-)?([0-1]?[0-9])(\/|-)?([0-9]{2}) ([0-9]{2}):([0-9]{2})(:[0-9]{2})?$#i", $value, $row)) {
                                $value = sprintf('20%d-%02d-%02d %02d:%02d:%02d', $row[5], $row[3], $row[1], $row[6], $row[7], (isset($row[9]) ? $row[9] : 0));
                            }

                            // Date inconnu
                            else {
                                $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) doit être une date valide";
                                if (!empty($current_rule['comment'])) {
                                    $errors[$key] .= " ({$current_rule['comment']})";
                                }

                                $errors[$key] .= '.';
                                continue;
                            }
                            break;

                        case 'bool':
                            if (in_array(mb_strtolower($value), [true, 'true', 'vrai', 'yes', 'oui'], true)) {
                                $value = 1;
                            } elseif (in_array(mb_strtolower($value), [false, 'false', 'faux', 'no', 'non'], true)) {
                                $value = 0;
                            } else {
                                $errors[$key] .= "Le champ {$current_rule['field_libelle']} ($key) doit être une valeur 1 (vrai) ou 0 (faux)";
                                continue;
                            }

                            break;
                    }
                }
            }

            // Champ OK
            if (!empty($current_rule['field_name'])) {
                $export[$current_rule['field_name']] = $value;
            }
        }

        // Champs non supportés par la matrice actuel
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (!empty($key)) {
                    $errors[$key] = "Le champ $key n'est pas supporté ou est mal nommé.";
                }
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        $this->export_array = $export;

        return true;
    }

    /**
     * Exporte les champs pour être sauvegarder dans une entité
     * L'export fournit un array pour être utiliser avec une entité.
     *
     * @return array
     */
    public function export_fields_to_array()
    {
        if (empty($this->export_array)) {
            throw new InvalidArgumentException('Il faut lancer check_fields($data) avec succès avant d\'exporter.');
        }

        return $this->export_array;
    }

    /**
     * Exporte les règles de la matrice sous forme d'un tableau html.
     */
    public function export_fields_to_user($title = null)
    {
        $rules = $this->rules;

        // Ré-écriture légère pour le tableau
        foreach ($rules as $key => $field) {
            if (!empty($field['expected_value'])) {
                if (preg_match('#,#', $field['expected_value'])) {
                    $rules[$key]['expected_value'] = str_replace(',', ' ou ', $field['expected_value']);
                } else {
                    $rules[$key]['expected_value'] = 'Valeur conditionnel';
                }
            } elseif (!empty($field['type'])) {
                if ('int' === $field['type']) {
                    $rules[$key]['expected_value'] = 'NUM';
                } else {
                    $rules[$key]['expected_value'] = mb_strtoupper($field['type']);
                }
            }
        }

        $table = call_user_func_array('\Jgauthi\Tools\Matrice\ArrayUtils::to_html_table_title_filter_col', [
            'data' => $rules,
            'title' => $title,
            'cols_display' => [
                'key' => 'Code du champ',
                'field_libelle' => 'Libellé',
                'required' => 'Requis',
                'expected_value' => 'Valeur attendue',
                'comment' => 'Commentaire',
            ],
            'charset' => 'utf-8',
        ]);

        $table = str_replace(
            ['<td class="required">0</td>', '<td class="required">1</td>'],
            ['<td class="required">Non</td>', '<td class="required"><strong>Oui</strong></td>'],
            $table
        );

        return $table;
    }
}

