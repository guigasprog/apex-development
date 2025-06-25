const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const PedidoProduto = sequelize.define('pedido_produto', {
  id: { type: DataTypes.INTEGER, autoIncrement: true, primaryKey: true },
  pedido_id: { type: DataTypes.INTEGER, allowNull: false },
  produto_id: { type: DataTypes.INTEGER, allowNull: false },
  quantidade: { type: DataTypes.INTEGER, allowNull: false }
}, { tableName: 'pedido_produto', timestamps: false });

module.exports = PedidoProduto;