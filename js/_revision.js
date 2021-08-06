/*global $, dotclear */
'use strict';

dotclear.revisionExpander = function () {
  $.expandContent({
    lines: $('#revisions-list tr.line'),
    callback: dotclear.viewRevisionContent,
  });
};

dotclear.viewRevisionContent = function (/*img, */ line, action) {
  action = action || 'toggle';
  if ($(line).attr('id') == undefined) {
    return;
  }

  const revisionId = $(line).attr('id').substr(1);
  const postId = $('#id').val();
  let tr = document.getElementById('re' + revisionId);
  if (!tr) {
    $.get(
      'services.php',
      {
        f: 'getPatch',
        pid: postId,
        rid: revisionId,
        type: dotclear.dcrevisions.post_type,
      },
      function (data) {
        const rsp = $(data).children('rsp')[0];
        if (rsp.attributes[0].value == 'ok') {
          // Patch found
          tr = document.createElement('tr');
          tr.id = 're' + revisionId;
          const td = document.createElement('td');
          td.colSpan = $(line).children('td').length;
          td.className = 'expand';
          tr.appendChild(td);

          const editor_mode = $('#post_format').get(0).value;
          let excerpt_nodes;
          let content_nodes;
          if (editor_mode == 'xhtml') {
            excerpt_nodes = $(rsp).find('post_excerpt_xhtml').children();
            content_nodes = $(rsp).find('post_content_xhtml').children();
          } else {
            excerpt_nodes = $(rsp).find('post_excerpt').children();
            content_nodes = $(rsp).find('post_content').children();
          }
          if (excerpt_nodes.length == 0 && content_nodes.length == 0) {
            $(td).append(`<strong>${dotclear.dcrevisions.msg.content_identical}</strong>`);
          } else {
            let table = '<table class="preview-rev">';
            table += dotclear.viewRevisionRender(excerpt_nodes, dotclear.dcrevisions.msg.excerpt, revisionId);
            table += dotclear.viewRevisionRender(content_nodes, dotclear.dcrevisions.msg.content, revisionId);
            table += '</table>';
            $(td).append(table);
          }
          $(line).addClass('expand');
          line.parentNode.insertBefore(tr, line.nextSibling);
        } else {
          $(line).toggleClass('expand');
          window.alert($(rsp).find('message').text());
        }
      }
    );
  } else {
    $(tr).toggle();
    $(line).toggleClass('expand');
  }
};

dotclear.viewRevisionRender = function (nodes, title) {
  let res = '';
  let lines = '';
  let previous = '';

  nodes.each(function (k) {
    const name = this.nodeName;
    const content = $(this).text();

    let ol = $(this).attr('oline') != undefined ? $(this).attr('oline') : '';
    let nl = $(this).attr('nline') != undefined ? $(this).attr('nline') : '';

    if (name == 'skip') {
      ol = nl = '&hellip;';
    }

    let tdclass = '';

    if (name == 'skip') {
      tdclass = ' skip';
    }
    if (name == 'context') {
      tdclass = ' context';
    }
    if (name == 'insert') {
      tdclass = ' insert';
    }
    if (name == 'delete') {
      tdclass = ' delete';
    }

    if (name != previous && (previous == '' || previous == 'context')) {
      tdclass += ' first';
    }
    const next = nodes.length > k + 1 ? nodes.get(k + 1).nodeName : '';
    if (name != next && next != 'insert' && next != 'delete') {
      tdclass += ' last';
    }

    previous = name;

    lines += `<tr>
 <td class="minimal col-line">${ol}</td>
 <td class="minimal col-line">${nl}</td>
 <td class="${tdclass}">${content}</td>
</tr>
`;
  });

  if (lines != '') {
    res = `<thead>
  <tr class="rev-header">
   <th colspan="3">${title}</th>
  </tr>
  <tr class="rev-number">
   <th class="minimal nowrap">${dotclear.dcrevisions.msg.current}</th>
   <th class="minimal nowrap">${dotclear.dcrevisions.msg.revision}</th>
   <th class="maximal"></th>
  </tr>
</thead>
<tbody>
${lines}
</tbody>
`;
  }

  return res;
};

$(function () {
  dotclear.dcrevisions = dotclear.getData('dcrevisions');
  $('#edit-entry').on('onetabload', function () {
    $('#revisions-area label').toggleWithLegend($('#revisions-area').children().not('label'), {
      user_pref: 'dcx_post_revisions',
      legend_click: true,
      fn: dotclear.revisionExpander(),
    });
    $('#revisions-list tr.line a.patch').on('click', function () {
      return window.confirm(dotclear.dcrevisions.msg.confirm_apply_patch);
    });
    $('#revpurge').on('click', function () {
      return window.confirm(dotclear.dcrevisions.msg.confirm_purge_revision);
    });
  });
});
