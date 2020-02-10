var rules = [];
var template = '';

// Load rules on page load and populate the table
jQuery(function() {
    reset_rules()
});


function reset_rules() {
    rules    = jQuery("#loadrules").html();
    if(!rules) rules = "null";
    rules    = jQuery.parseJSON(rules);
    template = jQuery("#loadtemplate").html();
    draw_table();
}

function test_rules() {
    var ToPost = [];
    ToPost['subject']    = 'Title';
    ToPost['auth']       = authkey;
    ToPost['submit']     = 'Edit';
    ToPost['rules']      = JSON.stringify(capture_rules());
    ToPost['test_title'] = jQuery('#test_title').val();

    ajax.post("tools.php?action=parser_test", ToPost, function (response) {
        jQuery("#testtable").find("tr:gt(2)").remove(); // clear table
        jQuery("#testtable tr:last").after(response);
    });    
}

function save_rules() {
    var ToPost = [];
    ToPost['id']       = 1;
    ToPost['subject']  = 'Title';
    ToPost['auth']     = authkey;
    ToPost['submit']   = 'Edit';
    ToPost['rules']    = JSON.stringify(capture_rules());
    ajax.post("tools.php?action=parser_alter", ToPost, function (response) {
        $('#response').raw().innerHTML = response;        
    });
}

function capture_rules() {
    rules = [];
    jQuery('#rulestable tr').each(function( index, row ) {
        if (row.id.match(/^rule_\d+$/)) {
            temp = jQuery(row).find(':input').serializeArray();
            rule = {};
            jQuery(temp).each(function(i, field){
                rule[field.name] = field.value;
            });
            rules.push(rule);
        }
    });
    return rules;
}

function export_rules(link) {
    var blob = new Blob([JSON.stringify(capture_rules())], {type: "text/text"});
    var url  = URL.createObjectURL(blob);

    // update link to new 'url'
    link.download    = "reparser_rules.json";
    link.href        = url;
}

function import_rules() {
    var file = document.getElementById('import_button').files[0];
    if (file.length <= 0) {
        return false;
    }

    var fr = new FileReader();

    fr.onload = function(e) { 
        rules = JSON.parse(e.target.result);
        draw_table();
    }

    fr.readAsText(file);
}

function add_rule() {
    rules = capture_rules();
    rule = {};
    rules.push(rule);
    draw_table();
}

function del_rule(i) {
    rules = capture_rules();
    rules.splice(i,1);
    draw_table();
}

function move_rule_up(i) {
    rules = capture_rules();
    rule = rules[i];
    rules[i]=rules[i-1];
    rules[i-1]=rule;
    draw_table();
}

function move_rule_down(i) {
    rules = capture_rules();
    rule = rules[i];
    rules[i]=rules[i+1];
    rules[i+1]=rule;
    draw_table();
}

function draw_table() {
   jQuery("#rulestable").find("tr:gt(1)").remove(); // clear table

   jQuery.each(rules, function(index, rule) {
       row=template;
       rowclass = (index%2 == 0) ? 'rowb' : 'rowa';
       row = row.replace('__ROWCLASS__', rowclass);
       row = row.replace(/__INDEX__/g,   index);

       // Basic replacements done, now convert the string to
       // a DOM element for further processing
       row = jQuery.parseHTML(row);

       if('pattern'       in rule) jQuery(row).find('[name=pattern]'  ).val(rule.pattern);
       if('replace'       in rule) jQuery(row).find('[name=replace]'  ).val(rule.replace);
       if('tvmazeid'      in rule) jQuery(row).find('[name=tvmazeid]' ).val(rule.tvmazeid);
       if(rule.overwrite === 'on') jQuery(row).find('[name=overwrite]').prop('checked', true);
       if(rule.tag       === 'on') jQuery(row).find('[name=tag]'      ).prop('checked', true);
       if(rule.break     === 'on') jQuery(row).find('[name=break]'    ).prop('checked', true);
       if(rule.append    === 'on') jQuery(row).find('[name=append]'   ).prop('checked', true);
       if('comment'       in rule) jQuery(row).find('[name=comment]'  ).val(rule.comment);
       jQuery('#rulestable tr:last').after(row)
   });
       jQuery('#rulestable tr:last')
              .after('<tr class="rowa"> \
                     <td><button style="parser_add_rule" onclick="add_rule()">add</button></td> \
                 </tr>');

}

