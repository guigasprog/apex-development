
const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Categoria = sequelize.define('categorias', {
idCategoria: {
type: DataTypes.INTEGER,
autoIncrement: true,
primaryKey: true,
field: 'idCategoria'
},
nome: {
type: DataTypes.STRING(100),
allowNull: false
},
descricao: {
type: DataTypes.TEXT
}
}, {
tableName: 'categorias',
timestamps: false
});

module.exports = Categoria;