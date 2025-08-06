const Cliente = require("../models/Cliente");
const Endereco = require("../models/Endereco");
const jwt = require("jsonwebtoken");
const crypto = require("crypto");
const mailer = require("../services/mailer");
const bcrypt = require("bcryptjs");
const sequelize = require("../config/database");

const generateToken = (id) => {
  return jwt.sign({ id }, process.env.JWT_SECRET, { expiresIn: "1d" });
};

exports.register = async (req, res) => {
  const t = await sequelize.transaction();

  try {
    const { nome, email, password, cpf, telefone, ...enderecoData } = req.body;

    const existingUser = await Cliente.findOne({ where: { email } });
    if (existingUser) {
      await t.rollback();
      return res.status(400).send({ error: "Este e-mail já está em uso." });
    }

    const novoEndereco = await Endereco.create(
      {
        logradouro: enderecoData.logradouro,
        numero: enderecoData.numero,
        complemento: enderecoData.complemento,
        bairro: enderecoData.bairro,
        cidade: enderecoData.cidade,
        estado: enderecoData.estado,
        cep: enderecoData.cep,
      },
      { transaction: t }
    );

    const cliente = await Cliente.create(
      {
        nome,
        email,
        password,
        cpf,
        telefone,
        endereco_id: novoEndereco.idEndereco,
      },
      { transaction: t }
    );

    await t.commit();

    const clienteResult = cliente.toJSON();
    delete clienteResult.password;

    return res.status(201).send({
      cliente: clienteResult,
      token: generateToken(cliente.id),
    });
  } catch (err) {
    await t.rollback();
    console.error(err);
    return res
      .status(400)
      .send({ error: "Falha no registro.", details: err.message });
  }
};

exports.login = async (req, res) => {
  const { email, password } = req.body;
  try {
    const cliente = await Cliente.findOne({
      where: { email },
      include: [{ model: Endereco, as: "endereco" }],
    });

    if (!cliente) {
      return res.status(401).send({ error: "Usuário não encontrado." });
    }

    if (!cliente.password || !(await cliente.comparePassword(password))) {
      return res.status(401).send({ error: "Senha inválida." });
    }

    const clienteResult = cliente.toJSON();
    delete clienteResult.password;

    res.send({
      cliente: clienteResult,
      token: generateToken(cliente.id),
    });
  } catch (err) {
    res.status(400).send({ error: "Falha no login." });
  }
};

exports.forgotPassword = async (req, res) => {
  const { email } = req.body;
  try {
    const cliente = await Cliente.findOne({ where: { email } });
    if (!cliente) {
      return res.status(200).send({
        message:
          "Se um usuário com este e-mail existir, um código foi enviado.",
      });
    }

    const code = crypto.randomInt(100000, 999999).toString();
    const now = new Date();
    now.setMinutes(now.getMinutes() + 10);

    const salt = await bcrypt.genSalt(10);
    const hashedCode = await bcrypt.hash(code, salt);

    await cliente.update({
      reset_token: hashedCode,
      reset_token_expires: now,
    });

    const emailHtml = `
      <div style="font-family: Inter, sans-serif; background-color: #121212; color: #F5F5F5; padding: 20px; text-align: center;">
        <div style="max-width: 600px; margin: auto; background-color: #1E1E1E; border-radius: 8px; overflow: hidden; border: 1px solid #3f3f46;">
          <div style="padding: 30px;">
            <h2 style="color: #BEF264; font-size: 24px; margin: 0; font-weight: bold;">Vibe Vault</h2>
            <h3 style="font-size: 20px; color: #FFFFFF; margin-top: 20px; margin-bottom: 10px;">Recuperação de Senha</h3>
            <p style="color: #A1A1AA; font-size: 16px; line-height: 1.5;">Você solicitou a recuperação de senha. Use o seguinte código para continuar:</p>
            <div style="background-color: #121212; border-radius: 8px; padding: 15px 20px; margin: 30px 0;">
              <p style="font-size: 36px; letter-spacing: 8px; margin: 0; color: #BEF264; font-weight: bold; word-break: break-all;">${code}</p>
            </div>
            <p style="color: #A1A1AA; font-size: 14px;">Este código é válido por 10 minutos.</p>
            <p style="color: #A1A1AA; font-size: 12px; margin-top: 30px;">Se você não solicitou esta alteração, por favor ignore este e-mail.</p>
          </div>
        </div>
      </div>
    `;

    await mailer.sendMail({
      to: email,
      from: "Vibe Vault <no-reply@vibevault.com>",
      subject: "Recuperação de Senha - Vibe Vault",
      html: emailHtml,
    });

    res
      .status(200)
      .send({ message: "Código de verificação enviado para o seu e-mail." });
  } catch (err) {
    console.error("ERRO NO FORGOT-PASSWORD:", err);
    res
      .status(500)
      .send({ error: "Ocorreu um erro interno. Tente novamente mais tarde." });
  }
};

exports.verifyCode = async (req, res) => {
  const { email, code } = req.body;
  try {
    const cliente = await Cliente.findOne({ where: { email } });
    if (!cliente || !cliente.reset_token) {
      return res
        .status(400)
        .send({ error: "Código inválido ou não solicitado." });
    }
    if (new Date() > new Date(cliente.reset_token_expires)) {
      return res
        .status(400)
        .send({ error: "Código expirado. Por favor, solicite um novo." });
    }

    // Compara o código fornecido pelo usuário com o HASH salvo no banco
    const codeMatch = await bcrypt.compare(code, cliente.reset_token);
    if (!codeMatch) {
      return res.status(400).send({ error: "Código inválido." });
    }

    res.status(200).send({ message: "Código verificado com sucesso." });
  } catch (err) {
    res.status(500).send({ error: "Erro ao verificar o código." });
  }
};

exports.resetPassword = async (req, res) => {
  const { email, code, password } = req.body;
  try {
    const cliente = await Cliente.findOne({ where: { email } });
    if (
      !cliente ||
      !cliente.reset_token ||
      new Date() > new Date(cliente.reset_token_expires)
    ) {
      return res.status(400).send({
        error:
          "Solicitação inválida ou expirada. Por favor, solicite um novo código.",
      });
    }

    const codeMatch = await bcrypt.compare(code, cliente.reset_token);
    if (!codeMatch) {
      return res
        .status(400)
        .send({ error: "Código de verificação incorreto." });
    }

    await cliente.update({
      password: password,
      reset_token: null,
      reset_token_expires: null,
    });

    res.status(200).send({ message: "Senha redefinida com sucesso!" });
  } catch (err) {
    res.status(500).send({ error: "Erro ao redefinir a senha." });
  }
};
