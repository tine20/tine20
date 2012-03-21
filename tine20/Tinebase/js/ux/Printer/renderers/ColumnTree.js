/**
 * @class Ext.ux.Printer.ColumnTreeRenderer
 * @extends Ext.ux.Printer.BaseRenderer
 * @author Ed Spencer
 * Helper class to easily print the contents of a column tree
 */
Ext.ux.Printer.ColumnTreeRenderer = Ext.extend(Ext.ux.Printer.BaseRenderer, {

  /**
   * Generates the body HTML for the tree
   * @param {Ext.tree.ColumnTree} tree The tree to print
   */
  generateBody: function(tree) {
    var columns = this.getColumns(tree);
    
    //use the headerTpl and bodyTpl XTemplates to create the main XTemplate below
    var headings = this.headerTpl.apply(columns);
    var body     = this.bodyTpl.apply(columns);
    
    return String.format('<table>{0}<tpl for=".">{1}</tpl></table>', headings, body);
  },
    
  /**
   * Returns the array of columns from a tree
   * @param {Ext.tree.ColumnTree} tree The tree to get columns from
   * @return {Array} The array of tree columns
   */
  getColumns: function(tree) {
    return tree.columns;
  },
  
  /**
   * Descends down the tree from the root, creating an array of data suitable for use in an XTemplate
   * @param {Ext.tree.ColumnTree} tree The column tree
   * @return {Array} Data suitable for use in the body XTemplate
   */
  prepareData: function(tree) {
    var root = tree.root,
        data = [],
        cols = this.getColumns(tree),
        padding = this.indentPadding;
        
    var f = function(node) {
      if (node.hidden === true || node.isHiddenRoot() === true) return;
      
      var row = Ext.apply({depth: node.getDepth() * padding}, node.attributes);
      
      Ext.iterate(row, function(key, value) {
        Ext.each(cols, function(column) {
          if (column.dataIndex == key) {
            row[key] = column.renderer ? column.renderer(value) : value;
          }
        }, this);
      });
      
      //the property used in the first column is renamed to 'text' in node.attributes, so reassign it here
      row[this.getColumns(tree)[0].dataIndex] = node.attributes.text;
      
      data.push(row);
    };
    
    root.cascade(f, this);
    
    return data;
  },
  
  /**
   * @property indentPadding
   * @type Number
   * Number of pixels to indent node by. This is multiplied by the node depth, so a node with node.getDepth() == 3 will
   * be padded by 45 (or 3x your custom indentPadding)
   */
  indentPadding: 15,
  
  /**
   * @property headerTpl
   * @type Ext.XTemplate
   * The XTemplate used to create the headings row. By default this just uses <th> elements, override to provide your own
   */
  headerTpl:  new Ext.XTemplate(
    '<tr>',
      '<tpl for=".">',
        '<th width="{width}">{header}</th>',
      '</tpl>',
    '</tr>'
  ),
 
  /**
   * @property bodyTpl
   * @type Ext.XTemplate
   * The XTemplate used to create each row. This is used inside the 'print' function to build another XTemplate, to which the data
   * are then applied (see the escaped dataIndex attribute here - this ends up as "{dataIndex}")
   */
  bodyTpl:  new Ext.XTemplate(
    '<tr>',
      '<tpl for=".">',
        '<td style="padding-left: {[xindex == 1 ? "\\{depth\\}" : "0"]}px">\{{dataIndex}\}</td>',
      '</tpl>',
    '</tr>'
  )
});

Ext.ux.Printer.registerRenderer('columntree', Ext.ux.Printer.ColumnTreeRenderer);
