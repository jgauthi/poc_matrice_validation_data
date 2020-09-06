<?php
/*****************************************************************************************************
 * @name MatriceFields
 * @note: Tool used to process an array of data with respect to a matrix
 * @author: Jgauthi <github.com/jgauthi>, created at [21jun2018]
 * @version 0.9

 ******************************************************************************************************/
namespace Jgauthi\Tools\Matrice;

class MatriceFields
{
    private $rules;
    private $export_array = null;
    public $check_maxlength = false;

    public function __construct($rules)
    {
        $this->rules = $rules;
    }

    public function check_fields($data)
    {
        $errors = $export = array();

        foreach ($this->rules as $key => $current_rule) {
            // Récupération du champ et suppression de la liste
            if (isset($data[$key])) {
                $value = $data[$key];
                unset($data[$key]);
            } else $value = null;


            // Aucune valeur
            if ($value == null || $value == '') {
                if ($current_rule['required']) {
                    $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) doit être complété.";
                    continue;
                } // Ne pas sauvegarder ce champ dans l'export
                elseif ($value === null)
                    continue;
            } else {
                if ($this->check_maxlength && !empty($current_rule['maxlength'])) {
                    $count = strlen($value);
                    if ($count > $current_rule['maxlength']) {
                        $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) dépasse le nombre de caractère authorisé ({$current_rule['maxlength']}).";
                        continue;
                    }
                }

                if (!empty($current_rule['expected_value'])) {
                    if (preg_match('#,#', $current_rule['expected_value'])) {
                        $expected_value = explode(',', $current_rule['expected_value']);
                        if (!in_array($value, $expected_value)) {
                            $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) a une valeur incorrecte: {$value}" .
                                ", attendue: " . implode(' ou ', $expected_value);

                            if (!empty($current_rule['comment']))
                                $errors[$key] .= " ({$current_rule['comment']})";

                            continue;
                        }
                    } elseif (!preg_match("#{$current_rule['expected_value']}#i", $value)) {
                        $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) a une valeur incorrecte: {$value}";

                        if (!empty($current_rule['comment']))
                            $errors[$key] .= ', ' . $current_rule['comment'];

                        continue;
                    }
                }

                // Type de champs
                if (!empty($current_rule['type'])) switch ($current_rule['type']) {
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
                        if (!preg_match("#https?://([^/]+)/#", $value)) {
                            $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) doit être une url valide.";
                            continue;
                        }
                        break;


                    case 'date':
                        // Date US
                        if (preg_match("#^([0-9]{4})(\/|-)?([0-1]?[0-9])(\/|-)?([0-3]?[0-9])$#i", $value, $row))
                            $value = sprintf('%4d-%02d-%02d', $row[1], $row[3], $row[5]);

                        // Date FR
                        elseif (preg_match("#^([0-3]?[0-9])(\/|-)?([0-1]?[0-9])(\/|-)?([0-9]{4})$#i", $value, $row))
                            $value = sprintf('%4d-%02d-%02d', $row[5], $row[3], $row[1]);

                        // Date FR (année courte)
                        elseif (preg_match("#^([0-3]?[0-9])(\/|-)?([0-1]?[0-9])(\/|-)?([0-9]{2})$#i", $value, $row))
                            $value = sprintf('20%d-%02d-%02d', $row[5], $row[3], $row[1]);

                        // Date inconnu
                        else {
                            $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) doit être une date valide";
                            if (!empty($current_rule['comment']))
                                $errors[$key] .= " ({$current_rule['comment']})";

                            $errors[$key] .= '.';
                            continue;
                        }
                        break;


                    case 'datetime':
                        // Date + Heure US
                        if (preg_match("#^([0-9]{4})(\/|-)?([0-1]?[0-9])(\/|-)?([0-3]?[0-9]) ([0-9]{2}):([0-9]{2})(:[0-9]{2})?$#i", $value, $row))
                            $value = sprintf('%4d-%02d-%02d %02d:%02d:%02d', $row[1], $row[3], $row[5], $row[6], $row[7], (isset($row[9]) ? $row[9] : 0));

                        // Date + Heure FR
                        elseif (preg_match("#^([0-3]?[0-9])(\/|-)?([0-1]?[0-9])(\/|-)?([0-9]{4}) ([0-9]{2}):([0-9]{2})(:[0-9]{2})?$#i", $value, $row))
                            $value = sprintf('%4d-%02d-%02d %02d:%02d:%02d', $row[5], $row[3], $row[1], $row[6], $row[7], (isset($row[9]) ? $row[9] : 0));

                        // Date + Heure FR (année courte)
                        elseif (preg_match("#^([0-3]?[0-9])(\/|-)?([0-1]?[0-9])(\/|-)?([0-9]{2}) ([0-9]{2}):([0-9]{2})(:[0-9]{2})?$#i", $value, $row))
                            $value = sprintf('20%d-%02d-%02d %02d:%02d:%02d', $row[5], $row[3], $row[1], $row[6], $row[7], (isset($row[9]) ? $row[9] : 0));

                        // Date inconnu
                        else {
                            $errors[$key] = "Le champ {$current_rule['field_libelle']} ($key) doit être une date valide";
                            if (!empty($current_rule['comment']))
                                $errors[$key] .= " ({$current_rule['comment']})";

                            $errors[$key] .= '.';
                            continue;
                        }
                        break;


                    case 'bool':
                        if (in_array(strtolower($value), array(true, 'true', 'vrai', 'yes', 'oui')))
                            $value = 1;
                        elseif (in_array(strtolower($value), array(false, 'false', 'faux', 'no', 'non')))
                            $value = 0;
                        else {
                            $errors[$key] .= "Le champ {$current_rule['field_libelle']} ($key) doit être une valeur 1 (vrai) ou 0 (faux)";
                            continue;
                        }

                        break;
                }
            }


            // Champ OK
            if (!empty($current_rule['field_name']))
                $export[$current_rule['field_name']] = $value;
        }

        // Champs non supportés par la matrice actuel
        if (!empty($data))
            foreach ($data as $key => $value)
                if (!empty($key))
                    $errors[$key] = "Le champ $key n'est pas supporté ou est mal nommé.";

        if (!empty($errors))
            return $errors;

        $this->export_array = $export;
        return true;
    }

    // Exporte les champs pour être sauvegarder dans une entité
    // L'export fournit un array pour être utiliser avec une entité
    public function export_fields_to_array()
    {
        if (empty($this->export_array))
            return !user_error('Il faut lancer check_fields($data) avec succès avant d\'exporter.');

        return $this->export_array;
    }

    // Exporte les règles de la matrice sous forme d'un tableau html
    public function export_fields_to_user($title = null)
    {
        $rules = $this->rules;

        // Ré-écriture légère pour le tableau
        foreach ($rules as $key => $field) {
            if (!empty($field['expected_value']))
                $rules[$key]['expected_value'] = str_replace(',', ' ou ', $field['expected_value']);
            elseif (!empty($field['type'])) {
                if ($field['type'] == 'int')
                    $rules[$key]['expected_value'] = 'NUM';
                else $rules[$key]['expected_value'] = strtoupper($field['type']);
            }
        }


        $table = call_user_func_array('\Jgauthi\Tools\Matrice\ArrayUtils::to_html_table_title_filter_col', array
        (
            'data' => $rules,
            'title' => $title,
            'cols_display' => array
            (
                'key' => 'Code du champ',
                'field_libelle' => 'Libellé',
                'required' => 'Requis',
                'expected_value' => 'Valeur attendue',
                'comment' => 'Commentaire',
            ),
            'charset' => 'utf-8',
        ));

        $table = str_replace
        (
            array('<td class="required">0</td>', '<td class="required">1</td>'),
            array('<td class="required">Non</td>', '<td class="required"><strong>Oui</strong></td>'),
            $table
        );

        return $table;
    }
}

