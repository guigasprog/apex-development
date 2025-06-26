const Cliente = require('../models/Cliente');
const Endereco = require('../models/Endereco');
const jwt = require('jsonwebtoken');
const sequelize = require('../config/database');

const generateToken = (id) => {
  return jwt.sign({ id }, process.env.JWT_SECRET, { expiresIn: '1d' });
};

exports.register = async (req, res) => {
  const t = await sequelize.transaction();
  
  try {
    const { nome, email, password, cpf, telefone, ...enderecoData } = req.body;

    const existingUser = await Cliente.findOne({ where: { email } });
    if (existingUser) {
      await t.rollback();
      return res.status(400).send({ error: 'Este e-mail já está em uso.' });
    }

    const novoEndereco = await Endereco.create({
      logradouro: enderecoData.logradouro,
      numero: enderecoData.numero,
      complemento: enderecoData.complemento,
      bairro: enderecoData.bairro,
      cidade: enderecoData.cidade,
      estado: enderecoData.estado,
      cep: enderecoData.cep,
    }, { transaction: t });

    const cliente = await Cliente.create({
      nome,
      email,
      password,
      cpf,
      telefone,
      endereco_id: novoEndereco.idEndereco
    }, { transaction: t });

    await t.commit();

    const clienteResult = cliente.toJSON();
    delete clienteResult.password;

    return res.status(201).send({
      cliente: clienteResult,
      token: generateToken(cliente.id)
    });

  } catch (err) {
    await t.rollback();
    console.error(err);
    return res.status(400).send({ error: 'Falha no registro.', details: err.message });
  }
};

exports.login = async (req, res) => {
  const { email, password } = req.body;
  try {
    const cliente = await Cliente.findOne({ 
      where: { email },
      include: [{ model: Endereco, as: 'endereco' }]
    });

    if (!cliente) {
      return res.status(401).send({ error: 'Usuário não encontrado.' });
    }

    if (!cliente.password || !(await cliente.comparePassword(password))) {
      return res.status(401).send({ error: 'Senha inválida.' });
    }
    
    const clienteResult = cliente.toJSON();
    delete clienteResult.password;

    res.send({
      cliente: clienteResult,
      token: generateToken(cliente.id)
    });
  } catch (err) {
    res.status(400).send({ error: 'Falha no login.' });
  }
};