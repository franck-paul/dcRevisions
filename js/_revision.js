/*global dotclear */
'use strict';

dotclear.ready(() => {
  dotclear.dcRevisions = dotclear.getData('dcrevisions');

  const renderRevision = (nodes, title) => {
    let lines = '';
    let previous = '';
    let index = 0;

    for (const node of nodes) {
      const name = node.nodeName;
      const content = node.textContent;

      let ol = node.getAttribute('oline') ?? '';
      let nl = node.getAttribute('nline') ?? '';

      if (name === 'skip') {
        ol = nl = '&hellip;';
      }

      let tdclass = ['skip', 'context', 'insert', 'delete'].includes(name) ? ` ${name}` : '';

      if (name !== previous && (previous === '' || previous === 'context')) {
        tdclass += ' first';
      }
      const next = nodes.length > index + 1 ? nodes[index + 1].nodeName : '';
      if (name !== next && next !== 'insert' && next !== 'delete') {
        tdclass += ' last';
      }

      previous = name;
      lines += `<tr><td class="minimal col-line">${ol}</td><td class="minimal col-line">${nl}</td><td class="${tdclass}">${content}</td></tr>`;

      index++;
    }

    if (lines !== '') {
      return `<thead><tr class="rev-header"><th colspan="3">${title}</th></tr><tr class="rev-number"><th class="minimal nowrap">${dotclear.dcRevisions.msg.current}</th><th class="minimal nowrap">${dotclear.dcRevisions.msg.revision}</th><th class="maximal"></th></tr></thead><tbody>${lines}</tbody>`;
    }

    return '';
  };

  const viewRevision = (line, _action = 'toggle', _event = null) => {
    if (line.getAttribute('id') === null) return;

    const revisionId = line.getAttribute('id').substring(1);
    const postId = document.getElementById('id').value;
    let tr = document.getElementById(`re${revisionId}`);
    if (tr) {
      tr.style.display = tr.style.display === 'none' ? '' : 'none';
      line.classList.toggle('expand');
    } else {
      dotclear.servicesGet(
        'getPatch',
        (data) => {
          const xml = new DOMParser().parseFromString(data, 'text/xml');
          if (xml.querySelector('rsp')?.getAttribute('status') === 'ok') {
            // Patch found
            tr = document.createElement('tr');
            tr.id = `re${revisionId}`;
            const td = document.createElement('td');
            td.colSpan = line.querySelectorAll('td').length;
            td.className = 'expand';
            tr.appendChild(td);

            const editor_mode = document.getElementById('post_format').value;
            let excerpt_nodes;
            let content_nodes;
            if (editor_mode === 'xhtml') {
              excerpt_nodes = xml.querySelector('post_excerpt_xhtml').childNodes;
              content_nodes = xml.querySelector('post_content_xhtml').childNodes;
            } else {
              excerpt_nodes = xml.querySelector('post_excerpt').childNodes;
              content_nodes = xml.querySelector('post_content').childNodes;
            }
            if (excerpt_nodes.length === 0 && content_nodes.length === 0) {
              td.append(...dotclear.htmlToNodes(`<strong>${dotclear.dcRevisions.msg.content_identical}</strong>`));
            } else {
              let table = '<table class="preview-rev">';
              table += renderRevision(excerpt_nodes, dotclear.dcRevisions.msg.excerpt, revisionId);
              table += renderRevision(content_nodes, dotclear.dcRevisions.msg.content, revisionId);
              table += '</table>';
              td.append(...dotclear.htmlToNodes(table));
            }
            line.classList.add('expand');
            line.parentNode.insertBefore(tr, line.nextSibling);
            return;
          }
          line.classList.toggle('expand');
          window.alert(xml.querySelector('message')?.textContent);
        },
        {
          pid: postId,
          rid: revisionId,
          type: dotclear.dcRevisions.post_type,
        },
      );
    }
  };

  const expandRevision = () => {
    dotclear.expandContent({
      lines: document.querySelectorAll('#revisions-list tr.line'),
      callback: viewRevision,
    });
  };

  dotclear.toggleWithDetails(document.getElementById('revisions-details'), {
    user_pref: 'dcx_post_revisions',
    fn: expandRevision(),
  });

  for (const patch of document.querySelectorAll('#revisions-list tr.line a.patch')) {
    patch.addEventListener('click', (event) => dotclear.confirm(dotclear.dcRevisions.msg.confirm_apply_patch, event));
  }
  document
    .getElementById('revpurge')
    ?.addEventListener('click', (event) => dotclear.confirm(dotclear.dcRevisions.msg.confirm_purge_revision, event));

  dotclear.responsiveCellHeaders(document.querySelector('#revisions-list'), '#revisions-list', 1, true);
});
