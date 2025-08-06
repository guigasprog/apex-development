const { DataTypes } = require("sequelize");
const sequelize = require("../config/database");
const bcrypt = require("bcryptjs");
const Endereco = require("./Endereco"); // Importa o modelo de Endereco

const Cliente = sequelize.define(
  "clientes",
  {
    id: {
      type: DataTypes.INTEGER,
      autoIncrement: true,
      primaryKey: true,
    },
    nome: { type: DataTypes.STRING(100), allowNull: false },
    email: { type: DataTypes.STRING(100), allowNull: false, unique: true },
    telefone: { type: DataTypes.STRING(20) },
    cpf: { type: DataTypes.STRING(14), allowNull: false, unique: true },
    password: { type: DataTypes.STRING, allowNull: false },
    reset_token: {
      type: DataTypes.STRING,
      allowNull: true,
    },
    reset_token_expires: {
      type: DataTypes.DATE,
      allowNull: true,
    },
    endereco_id: { type: DataTypes.INTEGER },
  },
  {
    tableName: "clientes",
    timestamps: false,
    hooks: {
      beforeCreate: async (cliente) => {
        if (cliente.password) {
          const salt = await bcrypt.genSalt(10);
          cliente.password = await bcrypt.hash(cliente.password, salt);
        }
      },

      beforeUpdate: async (cliente) => {
        if (cliente.changed("password")) {
          const salt = await bcrypt.genSalt(10);
          cliente.password = await bcrypt.hash(cliente.password, salt);
        }
      },
    },
  }
);

Cliente.belongsTo(Endereco, { foreignKey: "endereco_id", as: "endereco" });

Cliente.prototype.comparePassword = function (candidatePassword) {
  return bcrypt.compare(candidatePassword, this.password);
};

module.exports = Cliente;
