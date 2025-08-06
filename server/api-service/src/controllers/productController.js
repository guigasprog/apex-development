const {
  Produto,
  Categoria,
  ImagemProduto,
  ProdutoRelevancia,
  sequelize,
} = require("../models");
const { Op, where } = require("sequelize");

exports.getAllProducts = async (req, res) => {
  try {
    const allProducts = await Produto.findAll({
      include: [
        { model: Categoria, as: "categoria", required: false },
        {
          model: ImagemProduto,
          as: "imagens",
          limit: 1,
          order: [["id", "ASC"]],
        },
      ],
      attributes: {
        include: [
          [
            sequelize.literal(
              `(SELECT COUNT(*) FROM produto_relevancia WHERE produto_relevancia.produto_id = produtos.id)`
            ),
            "view_count",
          ],
        ],
      },
      order: [
        [sequelize.literal("view_count"), "DESC"],
        ["id", "DESC"],
      ],
    });

    const initialGroups = allProducts.reduce((acc, product) => {
      const categoryName =
        product.categoria != null
          ? product.categoria.nome
          : "Produtos Relevantes";
      if (!acc[categoryName]) {
        acc[categoryName] = [];
      }
      acc[categoryName].push(product);
      return acc;
    }, {});

    res.status(200).json(initialGroups);
  } catch (error) {
    console.error("Erro ao buscar produtos relevantes:", error);
    res.status(500).json({
      error: "Erro ao buscar produtos relevantes.",
      details: error.message,
    });
  }
};

exports.getProductById = async (req, res) => {
  try {
    const { id } = req.params;
    const product = await Produto.findByPk(id, {
      include: [
        { model: Categoria, as: "categoria" },
        { model: ImagemProduto, as: "imagens" },
      ],
    });
    if (!product) {
      return res.status(404).json({ error: "Produto não encontrado." });
    }
    res.status(200).json(product);
  } catch (error) {
    res
      .status(500)
      .json({ error: "Erro ao buscar o produto.", details: error.message });
  }
};

exports.trackProductView = async (req, res) => {
  try {
    const { id } = req.params;
    await ProdutoRelevancia.create({ produto_id: id, tipo_relevancia: "view" });
    res.status(200).send({ message: "View tracked." });
  } catch (error) {
    console.error("Falha ao rastrear visualização:", error.message);
    res.status(200).send({ message: "Falha silenciosa no rastreamento." });
  }
};

exports.getSearchSuggestions = async (req, res) => {
  const { q } = req.query;
  let id;
  if (req.id) id = req.id;
  if (!q || q.length < 2) return res.json([]);

  try {
    const suggestions = await Produto.findAll({
      where: { nome: { [Op.like]: `%${q}%` } },
      attributes: [
        "id",
        "nome",
        [
          sequelize.literal(
            `(SELECT COUNT(*) FROM produto_relevancia WHERE produto_relevancia.produto_id = produtos.id)`
          ),
          "view_count",
        ],
      ],
      order: [[sequelize.literal("view_count"), "DESC"]],
      limit: 10,
    });
    console.log("----+++----");
    let search = buscarProdutos(q, id, suggestions, res);
    console.log(search);
    console.log("-----------");
    res.status(200).json({ suggestions: suggestions, id_search: search.id });
  } catch (error) {
    console.error("Erro ao buscar sugestões:", error);
    res.status(500).json({ error: "Erro ao buscar sugestões." });
  }
};

async function buscarProdutos(q, id, suggestions, res) {
  let searchRecord;

  for (const s of suggestions) {
    if (id) {
      searchRecord = await ProdutoRelevancia.findByPk(id);
      console.log(searchRecord);
      if (searchRecord) {
        await searchRecord.update({
          produto_id: s.id,
          tipo_relevancia: "search",
          texto_busca: q,
        });
      } else {
        searchRecord = await ProdutoRelevancia.create({
          produto_id: s.id,
          tipo_relevancia: "search",
          texto_busca: q,
        });
      }
    } else {
      searchRecord = await ProdutoRelevancia.create({
        produto_id: s.id,
        tipo_relevancia: "search",
        texto_busca: q,
      });
    }
  }

  return res.json(searchRecord);
}
