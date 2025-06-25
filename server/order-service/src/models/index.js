const sequelize = require('../config/database');
const Produto = require('./Produto');
const Pedido = require('./Pedido');
const PedidoProduto = require('./PedidoProduto');

Pedido.belongsToMany(Produto, { through: PedidoProduto, foreignKey: 'pedido_id' });
Produto.belongsToMany(Pedido, { through: PedidoProduto, foreignKey: 'produto_id' });

module.exports = {
  sequelize,
  Produto,
  Pedido,
  PedidoProduto
};