<?php

/**
 * @brief dcRevisions, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author TomTom, Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcRevisions;

use Dotclear\Core\Backend\Page;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;

class RevisionsList
{
    /**
     * Constructs a new instance.
     *
     * @param      null|MetaRecord  $rs     List of revisions
     */
    public function __construct(
        protected ?MetaRecord $rs
    ) {
    }

    /**
     * Return number of revisions in record
     */
    public function count(): int
    {
        return (int) $this->rs?->count();
    }

    /**
     * Return HTML code to display the revisions list.
     *
     * @param      string  $url    The url base for patching
     */
    public function display(string $url): string
    {
        if ($this->rs instanceof MetaRecord && !$this->rs->isEmpty()) {
            return (new Table('revisions-list'))
                ->class(['clear', 'maximal'])
                ->thead((new Thead())
                    ->rows([
                        (new Tr())
                            ->cols([
                                (new Th())
                                    ->text(__('Id')),
                                (new Th())
                                    ->class('nowrap')
                                    ->text(__('Author')),
                                (new Th())
                                    ->class('nowrap')
                                    ->text(__('Date')),
                                (new Th())
                                    ->class('nowrap')
                                    ->text(__('Status')),
                                (new Th())
                                    ->class('nowrap')
                                    ->text(__('Actions')),
                            ]),
                    ]))
                ->tbody((new Tbody())
                    ->rows($this->getLines($url)))
            ->render();
        }

        return (new Note())
            ->class(['clear', 'form-note'])
            ->text(__('No revision'))
        ->render();
    }

    /**
     * Gets the HTML code to display revisions lines.
     *
     * @param      string  $url    The url base for patching
     *
     * @return  array<int, Tr>
     */
    private function getLines(string $url): array
    {
        $lines = [];
        if (is_null($this->rs)) {
            return $lines;
        }

        $index = count($this->rs);

        // Back to UTC timezone in order to get correct revision datetime
        $current_timezone = Date::getTZ();
        Date::setTZ('UTC');

        while ($this->rs->fetch()) {
            $lines[] = (new Tr('r' . $this->rs->revision_id))
                ->class(['line', 'wide', $this->rs->canPatch() ? '' : 'offline'])
                ->cols([
                    (new Td())
                        ->class(['maximal', 'nowrap', 'rid'])
                        ->items([
                            (new Strong(sprintf(__('Revision #%s'), $index--))),
                        ]),
                    (new Td())
                        ->class('nowrap')
                        ->text($this->rs->getAuthorLink()),
                    (new Td())
                        ->class('nowrap')
                        ->text($this->rs->getDate() . ' - ' . $this->rs->getTime()),
                    (new Td())
                        ->class(['minimal', 'nowrap', 'status'])
                        ->items([
                            (new Img('images/' . ($this->rs->canPatch() ? 'check-on.svg' : 'locker.svg')))
                                ->alt(($this->rs->canPatch() ? __('Revision allowed') : __('Revision blocked')))
                                ->title(($this->rs->canPatch() ? __('Revision allowed') : __('Revision blocked')))
                                ->class(['mark', 'mark-' . ($this->rs->canPatch() ? 'published' : 'locked')]),
                        ]),
                    (new Td())
                        ->class(['minimal', 'nowrap', 'status'])
                        ->items([
                            $this->rs->canPatch() ?
                            (new Link())
                                ->href(sprintf($url, $this->rs->revision_id))
                                ->title(__('Apply patch'))
                                ->class('patch')
                                ->items([
                                    (new Img(urldecode(Page::getPF('dcRevisions/images/apply.png'))))
                                        ->alt(__('Apply patch')),
                                ]) :
                            (new None()),
                        ]),
                ]);
        }

        // Restore previous timezone
        Date::setTZ($current_timezone);

        return $lines;
    }
}
