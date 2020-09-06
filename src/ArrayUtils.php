<?php
namespace Jgauthi\Tools\Matrice;

use InvalidArgumentException;

class ArrayUtils
{
    /**
     * Retourne un array sous forme de tableau html avec l'affichage du choix des colonnes et leur libellé.
     *
     * @param array $data [ 'title1' => ['product1' => 'val1', 'product2' => 'val2'], 'title2' => [...] ]
     * @param string|null $title_table optional
     * @param array $cols_display Liste des champs à afficher: [ 'code_champ' => 'libellé champ', 'code_champ2' => 'libellé champ2', ... ]
     * @param string $encode UTF-8 or ISO-8859-1
     *
     * @return string HTML Table
     */
    static public function to_html_table_title_filter_col($data, $title_table, $cols_display, $encode = 'UTF-8')
    {
        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException('Argument data is empty or is not an array.');
        }

        $html = '<table class="table table-striped" border="1">';
        if (!empty($title_table)) {
            $html .= '<caption>'.htmlentities($title_table, ENT_COMPAT, $encode).'</caption>';
        }

        // Titre
        $html .= '<thead class="thead-dark"><tr>';
        foreach ($cols_display as $key => $title) {
            $html .= '<th scope="col" class="'.$key.'">'.htmlentities($title, ENT_QUOTES, $encode).'</th>';
        }

        $html .= '</tr></thead>';

        // Contenu
        $html .= '<tbody>';
        foreach ($data as $col => $array) {
            $html .= '<tr>';
            foreach ($cols_display as $key => $title) {
                $content = null;
                if ('key' === $key) {
                    $content = $col;
                } elseif (isset($array[$key])) {
                    $content = $array[$key];
                }

                if (null === $content || '' === $content) {
                    $content = '&nbsp;';
                } elseif (is_array($content)) {
                    $content = htmlentities(var_export($content, true), ENT_QUOTES, $encode);
                } else {
                    $content = nl2br(htmlentities(trim($content), ENT_QUOTES, $encode));
                }

                $html .= '<td class="'.$key.'">'.$content.'</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>
	<tfoot>
		<tr>
			<td colspan="'.(count($cols_display) + 1).'">'.count($data).' elements in this table</td>
		</tr>
	</tfoot>
	</table>';

        return $html;
    }
}
