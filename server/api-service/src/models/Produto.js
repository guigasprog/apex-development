const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Produto = sequelize.define('produtos', {
  id: {
    type: DataTypes.INTEGER,
    autoIncrement: true,
    primaryKey: true,
  },
  nome: {
    type: DataTypes.STRING(255),
    allowNull: false,
  },
  descricao: {
    type: DataTypes.TEXT,
  },
  validade: {
    type: DataTypes.DATE,
  },
  preco: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false,
  },
  categoria_id: {
    type: DataTypes.INTEGER,
  },
}, {
  tableName: 'produtos',
  timestamps: false
});

module.exports = Produto;