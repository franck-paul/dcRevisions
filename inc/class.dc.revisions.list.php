<?php
/**
 * @brief dcRevisions, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author TomTom, Franck Paul and contributors
 *
 * @copyright TomTom, Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class dcRevisionsList
{
    protected $rs = null;

    public function __construct($rs)
    {
        $this->rs = $rs;
    }

    public function count()
    {
        return $this->rs->count();
    }

    public function display($url)
    {
        $res = '';
        if (!$this->rs->isEmpty()) {
            $html_block = '<table id="revisions-list" summary="' . __('Revisions') . '" class="clear maximal">' .
            '<thead>' .
            '<tr>' .
            '<th>' . __('Id') . '</th>' .
            '<th class="nowrap">' . __('Author') . '</th>' .
            '<th class="nowrap">' . __('Date') . '</th>' .
            '<th class="nowrap">' . __('Status') . '</th>' .
            '<th class="nowrap">' . __('Actions') . '</th>' .
                '</tr>' .
                '</thead>' .
                '<tbody>%s</tbody>' .
                '</table>';

            $res .= sprintf($html_block, $this->getLines($url));
        } else {
            $res .= '<p class="clear form-note">' . __('No revision') . '</p>';
        }

        return $res;
    }

    private function getLines($url)
    {
        $res    = '';
        $p_img  = '<img src="%1$s" alt="%2$s" title="%2$s" />';
        $p_link = '<a href="%1$s" title="%3$s" class="patch"><img src="%2$s" alt="%3$s" /></a>';
        $index  = is_countable($this->rs) ? count($this->rs) : 0;

        while ($this->rs->fetch()) {
            $res .= '<tr class="line wide' . (!$this->rs->canPatch() ? ' offline' : '') . '" id="r' . $this->rs->revision_id . '">' . "\n" .
            '<td class="maximal nowrap rid">' .
            '<strong>' . sprintf(__('Revision #%s'), $index--) . '</strong>' .
            "</td>\n" .
            '<td class="minimal nowrap">' .
            $this->rs->getAuthorLink() .
            "</td>\n" .
            '<td class="minimal nowrap">' .
            $this->rs->getDate() . ' - ' . $this->rs->getTime() .
            "</td>\n" .
            '<td class="minimal nowrap status">' .
            sprintf(
                $p_img,
                ('images/' . ($this->rs->canPatch() ? 'check-on.png' : 'locker.png')),
                ($this->rs->canPatch() ? __('Revision allowed') : __('Revision blocked'))
            ) .
                "</td>\n" .
                '<td class="minimal nowrap status">' .
                ($this->rs->canPatch() ? sprintf(
                    $p_link,
                    sprintf($url, $this->rs->revision_id),
                    urldecode(dcPage::getPF('dcRevisions/images/apply.png')),
                    __('Apply patch')
                ) : '') .
                "</td>\n" .
                "</tr>\n";
        }

        return $res;
    }
}
