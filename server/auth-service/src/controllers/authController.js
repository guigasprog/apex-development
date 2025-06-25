const Cliente = require('../models/Cliente');
const Endereco = require('../models/Endereco');
const jwt = require('jsonwebtoken');
const sequelize = require('../config/database');

const generateToken = (id) => {
  return jwt.sign({ id }, process.env.JWT_SECRET, { expiresIn: '1d' });
};

exports.register = async (req, res) => {
  const t = await sequelize.transaction(); // Inicia a transação
  
  try {
    const { nome, email, password, cpf, telefone, ...enderecoData } = req.body;

    const existingUser = await Cliente.findOne({ where: { email } });
    if (existingUser) {
      await t.rollback();
      return res.status(400).send({ error: 'Este e-mail já está em uso.' });
    }

    // 1. Cria o endereço dentro da transação
    const novoEndereco = await Endereco.create({
      logradouro: enderecoData.logradouro,
      numero: enderecoData.numero,
      complemento: enderecoData.complemento,
      bairro: enderecoData.bairro,
      cidade: enderecoData.cidade,
      estado: enderecoData.estado,
      cep: enderecoData.cep,
    }, { transaction: t });

    // 2. Cria o cliente com o ID do novo endereço, dentro da mesma transação
    const cliente = await Cliente.create({
      nome,
      email,
      password,
      cpf,
      telefone,
      endereco_id: novoEndereco.idEndereco
    }, { transaction: t });

    // Se tudo deu certo, confirma a transação
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
    // No login, também trazemos o endereço associado
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