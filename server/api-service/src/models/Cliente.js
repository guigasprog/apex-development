
const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Cliente = sequelize.define('clientes', {
  id: {
    type: DataTypes.INTEGER,
    autoIncrement: true,
    primaryKey: true
  },
  nome: {
    type: DataTypes.STRING(100),
    allowNull: false
  },
  email: {
    type: DataTypes.STRING(100),
    allowNull: false,
    unique: true
  },
  telefone: {
    type: DataTypes.STRING(20)
  },
  cpf: {
    type: DataTypes.STRING(14),
    allowNull: false,
    unique: true
  },
  endereco_id: {
    type: DataTypes.INTEGER
  },
  password: {
    type: DataTypes.STRING,
    allowNull: true 
  }
}, {
  tableName: 'clientes',
  timestamps: false,
});

module.exports = Cliente;