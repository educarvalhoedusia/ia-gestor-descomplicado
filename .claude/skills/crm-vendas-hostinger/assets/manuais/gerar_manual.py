#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Gera o Manual do Usuário do Painel Comercial em PDF, para o perfil
"admin" (manual completo) ou "vendedor" (só o que o vendedor usa).

Uso:
    python3 gerar_manual.py --empresa "Nome da Empresa" --perfil admin --out /caminho/Manual_Admin.pdf
    python3 gerar_manual.py --empresa "Nome da Empresa" --perfil vendedor --out /caminho/Manual_Vendedor.pdf --url https://dominio.com/crm/
"""

import argparse

from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, PageBreak, Table, TableStyle,
    ListFlowable, ListItem, HRFlowable,
)

VIOLET = colors.HexColor("#5B3FE0")
VIOLET_DEEP = colors.HexColor("#3F2BA8")
VIOLET_SOFT = colors.HexColor("#EFECFC")
INK = colors.HexColor("#211D3A")
ASH = colors.HexColor("#6B6885")


def build_styles():
    styles = getSampleStyleSheet()
    styles.add(ParagraphStyle(
        name="H1", parent=styles["Heading1"], fontName="Helvetica-Bold",
        fontSize=17, textColor=VIOLET_DEEP, spaceBefore=6, spaceAfter=10,
    ))
    styles.add(ParagraphStyle(
        name="H2", parent=styles["Heading2"], fontName="Helvetica-Bold",
        fontSize=12.5, textColor=INK, spaceBefore=14, spaceAfter=6,
    ))
    styles.add(ParagraphStyle(
        name="Body", parent=styles["Normal"], fontName="Helvetica",
        fontSize=10, leading=14.5, textColor=INK, spaceAfter=6,
    ))
    styles.add(ParagraphStyle(
        name="BodySmall", parent=styles["Normal"], fontName="Helvetica",
        fontSize=9, leading=13, textColor=ASH, spaceAfter=4,
    ))
    styles.add(ParagraphStyle(
        name="MyBullet", parent=styles["Normal"], fontName="Helvetica",
        fontSize=10, leading=14.5, textColor=INK, spaceAfter=3,
    ))
    styles.add(ParagraphStyle(
        name="CoverTitle", parent=styles["Title"], fontName="Helvetica-Bold",
        fontSize=28, textColor=colors.white, alignment=TA_CENTER, leading=32,
    ))
    styles.add(ParagraphStyle(
        name="CoverSub", parent=styles["Normal"], fontName="Helvetica",
        fontSize=13, textColor=colors.white, alignment=TA_CENTER, spaceBefore=10,
    ))
    styles.add(ParagraphStyle(
        name="TocEntry", parent=styles["Normal"], fontName="Helvetica",
        fontSize=11, leading=20, textColor=INK,
    ))
    return styles


def bullets(styles, items, style="MyBullet"):
    return ListFlowable(
        [ListItem(Paragraph(t, styles[style]), leftIndent=8, bulletColor=VIOLET) for t in items],
        bulletType="bullet", start="•", leftIndent=14, spaceBefore=2, spaceAfter=8,
    )


def hr():
    return HRFlowable(width="100%", thickness=0.6, color=colors.HexColor("#E3E1F0"), spaceBefore=4, spaceAfter=12)


def build_story(styles, empresa, perfil, url_exemplo):
    section_n = [0]

    def section(title):
        section_n[0] += 1
        return Paragraph(f"{section_n[0]}. {title}", styles["H1"])

    def subsection(title):
        return Paragraph(title, styles["H2"])

    story = []
    subtitulo = "Manual do Vendedor" if perfil == "vendedor" else "Manual do Usuário"

    # ---------- Capa ----------
    story.append(Spacer(1, 6 * cm))
    story.append(Paragraph(subtitulo, styles["CoverTitle"]))
    story.append(Paragraph(f"Painel Comercial {empresa}", styles["CoverSub"]))
    story.append(Spacer(1, 0.6 * cm))
    story.append(Paragraph("CRM de vendas — guia rápido de uso", styles["CoverSub"]))
    story.append(Spacer(1, 6 * cm))
    story.append(Paragraph(empresa, styles["CoverSub"]))
    story.append(PageBreak())

    # ---------- Sumário ----------
    if perfil == "admin":
        toc_items = [
            "O que é o Painel Comercial", "Como entrar no painel",
            "Cadastrando um novo contato (lead)", "Perfil DISC e como convencer cada perfil",
            "Assistente de resposta com IA", "Prospectar Clientes (busca no Google Maps + IA)",
            "Registrando conversas e propostas",
            "Editando ou removendo um contato", "Indicadores (KPIs) e filtros",
            "Alerta de follow-up atrasado", "Exportando dados (CSV, Excel e PDF)",
            "Ranking de vendas", "Administrador x Vendedor — o que cada um vê",
            "Área do administrador", "Boas práticas e segurança",
        ]
    else:
        toc_items = [
            "O que é o Painel Comercial", "Como entrar no painel",
            "Cadastrando um novo contato (lead)", "Perfil DISC e como convencer cada perfil",
            "Assistente de resposta com IA", "Prospectar Clientes (busca no Google Maps + IA)",
            "Registrando conversas e propostas",
            "Editando ou removendo um contato", "Indicadores (KPIs) e filtros",
            "Alerta de follow-up atrasado", "Exportando dados (CSV, Excel e PDF)",
            "Ranking de vendas", "O que é exclusivo do administrador",
            "Boas práticas e segurança",
        ]
    story.append(Paragraph("Sumário", styles["H1"]))
    story.append(Spacer(1, 6))
    for i, t in enumerate(toc_items, 1):
        story.append(Paragraph(f"{i}. {t}", styles["TocEntry"]))
    story.append(PageBreak())

    # ---------- 1. Visão geral ----------
    story.append(section("O que é o Painel Comercial"))
    if perfil == "vendedor":
        story.append(Paragraph(
            f"O Painel Comercial {empresa} é o CRM (sistema de gestão de vendas) que você usa para "
            "organizar seus leads e clientes: cadastro de contatos, histórico de conversas e o "
            "acompanhamento das suas propostas — tudo em um só lugar, sem depender de planilhas soltas.",
            styles["Body"]))
        story.append(Paragraph(
            "Você tem acesso aos <b>seus próprios leads</b> — os que estão sob sua responsabilidade. "
            "Os leads de outros vendedores não aparecem para você; só administradores enxergam a "
            "carteira completa da equipe.",
            styles["Body"]))
    else:
        story.append(Paragraph(
            f"O Painel Comercial {empresa} é o CRM (Customer Relationship Management) interno da "
            "equipe de vendas. Ele centraliza o cadastro de contatos (leads e clientes), o histórico "
            "de conversas com cada um deles, as propostas oferecidas e indicadores de desempenho "
            "comercial.",
            styles["Body"]))
        story.append(Paragraph(
            "Os dados ficam gravados em um banco de dados MySQL próprio, hospedado no Hostinger — "
            "não dependem de nenhuma ferramenta externa nem de planilhas soltas.",
            styles["Body"]))
        story.append(hr())
        story.append(subsection("Para que serve cada perfil de acesso"))
        story.append(bullets(styles, [
            "<b>Vendedor:</b> cadastra e acompanha os próprios leads, registra conversas e propostas, "
            "vê seus próprios indicadores e a classificação geral de vendas (sem ver o valor dos colegas).",
            "<b>Administrador:</b> tem acesso a todos os leads da equipe, aos valores de todas as "
            "propostas, ao relatório de acessos e ao cadastro/remoção de contas de usuário.",
        ]))

    # ---------- 2. Login ----------
    story.append(section("Como entrar no painel"))
    url_txt = url_exemplo or "https://SEUDOMINIO.com/crm/"
    story.append(Paragraph(
        f"Acesse o endereço do painel no navegador (ex.: <b>{url_txt}</b>) e informe seu e-mail e "
        "senha cadastrados.",
        styles["Body"]))
    story.append(bullets(styles, [
        "<b>Esqueci minha senha:</b> peça para um administrador te cadastrar novamente, ou trocar sua "
        'senha pela tela "Gerenciar equipe".',
        '<b>Trocar minha senha:</b> a qualquer momento, clique em "Trocar minha senha" no canto superior '
        "direito do painel e informe a nova senha (mínimo 6 caracteres).",
        '<b>Sair:</b> clique no botão "Sair" no canto superior direito para encerrar sua sessão — '
        "recomendado ao usar computadores compartilhados.",
    ]))

    # ---------- 3. Novo contato ----------
    story.append(section("Cadastrando um novo contato (lead)"))
    story.append(Paragraph(
        'Clique no botão <b>"+ Novo contato"</b>, no canto superior direito da lista de contatos, e '
        "preencha os campos:",
        styles["Body"]))
    story.append(bullets(styles, [
        "<b>Nome completo, Telefone/WhatsApp, Origem do lead e Vendedor responsável</b> são obrigatórios.",
        "<b>Empresa, Cargo, E-mail e Perfil DISC</b> são opcionais, mas ajudam a qualificar o contato.",
        "O <b>Perfil DISC</b> segue a convenção de cores: D = vermelho, I = amarelo, S = verde, C = azul.",
        'Se seu nome ainda não estiver na lista de vendedores, use a opção '
        '<b>"+ Adicionar novo vendedor..."</b> no próprio campo.',
    ]))
    story.append(Paragraph(
        "Depois de salvo, o contato aparece na sua lista, colorido de acordo com o perfil DISC "
        "identificado.",
        styles["Body"]))

    # ---------- 3b. Perfil DISC ----------
    story.append(section("Perfil DISC e como convencer cada perfil"))
    story.append(Paragraph(
        "O <b>DISC</b> é uma metodologia simples de perfil comportamental que ajuda a adaptar a "
        "forma de conversar com cada lead. O painel usa 4 letras: <b>D</b> (Dominante), "
        "<b>I</b> (Influente), <b>S</b> (Estável) e <b>C</b> (Conforme/Analítico).",
        styles["Body"]))
    story.append(subsection("Não sabe qual é o perfil? Deixe o painel te ajudar"))
    story.append(Paragraph(
        "No cadastro ou edição do contato, ao lado dos botões D/I/S/C, existe uma lista de frases "
        "típicas — clique na que mais se parece com o que o lead disse, e o perfil correspondente já "
        "é marcado automaticamente:",
        styles["Body"]))
    disc_table = [
        ["Se o lead disse algo como...", "Perfil provável"],
        ['"Vai direto ao ponto: quanto custa e quando começa?"', "D — Dominante"],
        ['"Adorei a ideia! Me conta mais sobre a experiência."', "I — Influente"],
        ['"Preciso pensar com calma e conversar com a equipe."', "S — Estável"],
        ['"Quero ver quem já usou e quais foram os resultados."', "C — Conforme/Analítico"],
    ]
    disc_tbl = Table(disc_table, colWidths=[11 * cm, 5.6 * cm])
    disc_tbl.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), VIOLET),
        ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
        ("FONTNAME", (0, 0), (-1, 0), "Helvetica-Bold"),
        ("FONTNAME", (0, 1), (-1, -1), "Helvetica"),
        ("FONTSIZE", (0, 0), (-1, -1), 8.6),
        ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, VIOLET_SOFT]),
        ("GRID", (0, 0), (-1, -1), 0.5, colors.HexColor("#E3E1F0")),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("TOPPADDING", (0, 0), (-1, -1), 6),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
        ("LEFTPADDING", (0, 0), (-1, -1), 8),
    ]))
    story.append(disc_tbl)
    story.append(Spacer(1, 8))
    story.append(subsection("Dica automática de como convencer cada perfil"))
    story.append(Paragraph(
        "Depois de salvar o contato com um perfil DISC identificado, abra o contato novamente: uma "
        "caixa amarela aparece com dicas prontas de como abordar aquele tipo de perfil (tom de "
        "conversa, o que enfatizar, o que evitar). Essa dica é automática, não precisa configurar "
        "nada.",
        styles["Body"]))

    # ---------- 3c. Assistente de resposta com IA ----------
    story.append(section("Assistente de resposta com IA"))
    story.append(Paragraph(
        "Dentro de cada contato, acima de \"Registrar nova conversa\", existe a caixa "
        "<b>\"🤖 Assistente de resposta (IA)\"</b>. Use quando o lead responder algo (uma objeção, "
        "uma dúvida, um \"vou pensar\") e você não souber exatamente o que responder:",
        styles["Body"]))
    story.append(bullets(styles, [
        "Cole no campo o que o comprador respondeu (por WhatsApp, e-mail etc.).",
        'Clique em <b>"Gerar sugestão de resposta"</b>.',
        "A IA lê o histórico da negociação (produto, valor, estágio, perfil DISC) e sugere um texto "
        "persuasivo para você enviar.",
        'Revise a sugestão, ajuste o que quiser, e clique em <b>"📱 Abrir no WhatsApp"</b> — abre a '
        "conversa já com o número do contato e o texto preenchido, só falta enviar. Também dá para "
        'clicar em <b>"📋 Copiar"</b> e colar em outro canal, como e-mail.',
    ]))
    story.append(Paragraph(
        "<b>Importante:</b> a IA só sugere — ela nunca envia nada sozinha para o lead. A decisão "
        "final é sempre sua.",
        styles["Body"]))
    if perfil == "admin":
        story.append(Paragraph(
            "Esse recurso depende de uma chave de API da Anthropic configurada no <b>config.php</b> "
            "do servidor (campo <code>anthropic_api_key</code>). Sem essa chave configurada, o botão "
            "aparece normalmente mas mostra uma mensagem de erro ao clicar — o resto do painel "
            "continua funcionando sem problema. Consulte o README.md do projeto para o passo a passo "
            "de configuração.",
            styles["BodySmall"]))

    # ---------- 3d. Prospectar Clientes ----------
    story.append(section("Prospectar Clientes (busca no Google Maps + IA)"))
    story.append(Paragraph(
        "A aba <b>\"🧭 Prospectar Clientes\"</b>, no topo do painel, ajuda a encontrar empresas em "
        "potencial numa cidade, sem precisar já ter o contato:",
        styles["Body"]))
    story.append(bullets(styles, [
        "Informe a <b>cidade</b> e o <b>ramo de atuação</b> (ex.: \"clínica odontológica\", "
        "\"escritório de contabilidade\", \"imobiliária\") e clique em "
        '<b>"🔍 Buscar empresas com IA"</b>.',
        "As empresas encontradas no Google Maps aparecem como <b>prospects</b> numa lista separada — "
        "não entram direto como lead no CRM, para você revisar antes.",
        "Em cada prospect, você vê endereço, telefone, site e a nota do Google.",
    ]))
    story.append(Paragraph(
        "<i>O Google Maps não usa código CNAE (isso é uma classificação da Receita Federal) — a busca "
        "funciona por palavra-chave/ramo de negócio, o que na prática cobre o mesmo objetivo.</i>",
        styles["BodySmall"]))
    story.append(subsection("Abordando e convertendo um prospect"))
    story.append(bullets(styles, [
        'Clique em <b>"✉️ Gerar para E-mail"</b> ou <b>"📱 Gerar para WhatsApp"</b> para a IA escrever '
        "uma mensagem de primeiro contato, já considerando o ramo de atuação da empresa.",
        'Revise e clique em <b>"Abrir e-mail"</b> ou <b>"Abrir WhatsApp"</b> — abre pronto para enviar, '
        "você decide o momento de apertar enviar.",
        "Quando a empresa responder, registre a resposta no campo próprio e atualize o "
        '<b>status</b> (Novo, Contatado, Respondeu, Descartado, Virou lead).',
        'Se valer a pena seguir, clique em <b>"✅ Adicionar como lead"</b> — vira um contato de '
        "verdade no CRM, na aba \"Contatos\", pronto para registrar conversas e propostas como "
        "qualquer outro lead.",
    ]))
    if perfil == "admin":
        story.append(Paragraph(
            "Esse recurso depende de uma chave de API do Google Maps (Places API) configurada no "
            "<b>config.php</b> (campo <code>google_maps_api_key</code>) e reaproveita a mesma chave "
            "da Anthropic já usada no Assistente de resposta. Sem a chave do Google Maps, a busca "
            "mostra uma mensagem de erro clara ao clicar — o resto do painel continua funcionando "
            "sem problema. Consulte o README.md do projeto para o passo a passo de configuração.",
            styles["BodySmall"]))

    story.append(PageBreak())

    # ---------- 4. Conversas ----------
    story.append(section("Registrando conversas e propostas"))
    story.append(Paragraph(
        "Selecione um contato na lista e, na seção “Registrar nova conversa”, preencha:",
        styles["Body"]))
    story.append(bullets(styles, [
        "<b>Data/hora, canal, resumo e temperatura do lead</b> (quente, morno ou frio).",
        "<b>Próxima ação combinada</b> e a <b>data de follow-up</b> — é essa data que gera o alerta de atraso.",
        "<b>Pilar, produto/serviço, valor da proposta e estágio da negociação</b> "
        "(prospecção → proposta enviada → em negociação → fechado-ganho/perdido).",
    ]))
    story.append(Paragraph(
        "Cada contato acumula um histórico completo de conversas — nada é substituído, tudo fica "
        "registrado em ordem cronológica.",
        styles["Body"]))

    # ---------- 5. Editar/remover ----------
    story.append(section("Editando ou removendo um contato"))
    story.append(bullets(styles, [
        '<b>Editar contato:</b> abra o contato e clique em "Editar contato" para corrigir dados '
        "cadastrais (nome, telefone, empresa, vendedor responsável, perfil DISC etc.).",
        '<b>Remover contato:</b> clique em "Remover contato" — a ação apaga também todo o histórico '
        "de conversas e não pode ser desfeita, por isso o painel pede confirmação.",
        '<b>Tags:</b> use o campo de tags (na tela do contato) para marcar segmentações livres, como '
        '"prioridade", "indicação VIP" etc.',
    ]))

    story.append(PageBreak())

    # ---------- 6. KPIs ----------
    story.append(section("Indicadores (KPIs) e filtros"))
    story.append(Paragraph("No topo do painel ficam os indicadores principais:", styles["Body"]))
    if perfil == "vendedor":
        story.append(bullets(styles, [
            "<b>Total de leads:</b> quantidade de contatos sob sua responsabilidade.",
            "<b>Taxa de conversão:</b> percentual dos seus leads que viraram negócio fechado-ganho.",
            '<b>Meu pipeline em aberto:</b> soma do valor das suas propostas ainda ativas.',
            "<b>Follow-ups atrasados:</b> quantidade das suas próximas ações com data já vencida.",
        ]))
    else:
        story.append(bullets(styles, [
            "<b>Total de leads:</b> quantidade de contatos que a equipe tem cadastrados.",
            "<b>Taxa de conversão:</b> percentual de leads que viraram negócio fechado-ganho.",
            '<b>Pipeline em aberto (total geral):</b> soma do valor de todas as propostas ativas da equipe.',
            "<b>Follow-ups atrasados:</b> quantidade de próximas ações com data já vencida.",
            "<b>Leads por vendedor:</b> distribuição dos contatos entre a equipe.",
        ]))
    story.append(Paragraph(
        "Logo abaixo, a barra de filtros permite buscar por nome/empresa/e-mail e filtrar por "
        "estágio da negociação, pilar, temperatura do lead e período.",
        styles["Body"]))

    # ---------- 7. Alerta de atraso ----------
    story.append(section("Alerta de follow-up atrasado"))
    story.append(Paragraph(
        "Quando existe pelo menos um follow-up com data de próxima ação vencida, uma faixa vermelha "
        'aparece no topo do painel. Clique nela — ou no botão "⚠ Só atrasados" na barra de '
        "ferramentas — para filtrar a lista e ver só esses contatos, e agir rápido antes que o lead "
        "esfrie.",
        styles["Body"]))

    # ---------- 8. Exportações ----------
    story.append(section("Exportando dados (CSV, Excel e PDF)"))
    story.append(Paragraph(
        "Na barra de ferramentas existem três botões de exportação, todos gerando um arquivo com o "
        "histórico completo dos seus contatos e conversas:",
        styles["Body"]))
    story.append(bullets(styles, [
        "<b>CSV:</b> formato de texto simples, ideal para abrir em qualquer planilha.",
        "<b>Excel (.xlsx):</b> já vem formatado como planilha, pronto para abrir no Excel/Google Sheets.",
        "<b>PDF:</b> gera um relatório tabular, bom para imprimir ou anexar em um e-mail.",
    ]))

    # ---------- 9. Ranking ----------
    story.append(section("Ranking de vendas"))
    story.append(Paragraph(
        'Clique no botão <b>"🏆 Ranking"</b> para ver a classificação geral da equipe, ordenada pelo '
        "número de negócios fechados-ganho.",
        styles["Body"]))
    story.append(bullets(styles, [
        "Você enxerga a <b>posição de todo mundo</b> e a <b>quantidade</b> de negócios fechados de "
        "cada vendedor.",
        "O <b>valor em R$</b> só aparece na sua própria linha (destacada) — as linhas dos colegas "
        "mostram um cadeado 🔒 no lugar do valor.",
    ]))

    story.append(PageBreak())

    if perfil == "admin":
        story.append(section("Administrador x Vendedor — o que cada um vê"))
        table_data = [
            ["Recurso", "Vendedor", "Administrador"],
            ["Ver e cadastrar contatos", "Só os próprios leads", "Todos os leads da equipe"],
            ["Ver valor das propostas", "Só as próprias", "Todas"],
            ["KPI de pipeline", "Só o próprio total", "Total geral da equipe"],
            ["Ranking de vendas", "Posição de todos, valor só o próprio", "Posição e valor de todos"],
            ["Editar/remover contato", "Sim (os próprios)", "Sim (todos)"],
            ["Exportar CSV/Excel/PDF", "Sim (os próprios dados)", "Sim (todos os dados)"],
            ["Gerenciar equipe (cadastrar/remover contas)", "Não", "Sim"],
            ["Relatório de acessos", "Não", "Sim"],
        ]
        tbl = Table(table_data, colWidths=[6.6 * cm, 5 * cm, 5 * cm])
        tbl.setStyle(TableStyle([
            ("BACKGROUND", (0, 0), (-1, 0), VIOLET),
            ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
            ("FONTNAME", (0, 0), (-1, 0), "Helvetica-Bold"),
            ("FONTNAME", (0, 1), (-1, -1), "Helvetica"),
            ("FONTSIZE", (0, 0), (-1, -1), 8.6),
            ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, VIOLET_SOFT]),
            ("GRID", (0, 0), (-1, -1), 0.5, colors.HexColor("#E3E1F0")),
            ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
            ("TOPPADDING", (0, 0), (-1, -1), 6),
            ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
            ("LEFTPADDING", (0, 0), (-1, -1), 8),
        ]))
        story.append(tbl)
        story.append(Spacer(1, 10))

        story.append(section("Área do administrador"))
        story.append(subsection("Gerenciar equipe"))
        story.append(Paragraph(
            'Botão visível só para administradores. Permite <b>adicionar</b> uma nova conta (nome, '
            "e-mail e senha inicial — a pessoa deve trocá-la no primeiro acesso) e <b>remover</b> "
            'contas de quem saiu da equipe. Ao adicionar alguém, o nome já entra automaticamente na '
            'lista de "Vendedor responsável".',
            styles["Body"]))
        story.append(subsection("Relatório de acessos"))
        story.append(Paragraph(
            "Mostra os últimos 300 logins da equipe: nome, e-mail, data/hora e endereço IP de "
            "origem — útil para acompanhar quem está usando o painel e quando.",
            styles["Body"]))
        story.append(subsection("E-mails automáticos de follow-up"))
        story.append(Paragraph(
            "O painel pode enviar e-mails sozinho, uma vez por dia, quando a data de follow-up de "
            "uma interação chega: um e-mail para o lead (se tiver e-mail cadastrado) e um alerta "
            "interno para o vendedor responsável. Esse recurso é opcional e configurado uma única "
            "vez via <b>Cron Job</b> no hPanel do Hostinger — consulte o README.md do projeto "
            "(seção \"E-mails automáticos de follow-up\") para o passo a passo completo.",
            styles["Body"]))
    else:
        story.append(section("O que é exclusivo do administrador"))
        story.append(Paragraph(
            "Algumas telas do painel só aparecem para quem é administrador. Isso não afeta seu "
            "trabalho do dia a dia — é só para você saber que elas existem:",
            styles["Body"]))
        story.append(bullets(styles, [
            "<b>Ver os leads de toda a equipe</b> (você só vê os seus).",
            "<b>Ver o valor das propostas dos colegas</b> e o pipeline total da equipe.",
            '<b>"Gerenciar equipe":</b> cadastrar ou remover contas de usuário.',
            '<b>"Relatório de acessos":</b> ver quando cada pessoa entrou no painel.',
        ]))
        story.append(Paragraph(
            "Se precisar de algo dessa lista (por exemplo, mudar o vendedor responsável por um lead "
            "que não é seu), peça para um administrador.",
            styles["Body"]))

    # ---------- Segurança ----------
    story.append(section("Boas práticas e segurança"))
    story.append(bullets(styles, [
        "Nunca compartilhe sua senha com colegas — cada pessoa deve ter sua própria conta.",
        'Troque a senha inicial recebida assim que possível, pelo botão "Trocar minha senha".',
        'Clique em "Sair" ao terminar de usar o painel em computadores compartilhados.',
        "Use o botão de exportação (CSV/Excel/PDF) periodicamente como backup adicional dos seus dados.",
        "Em caso de dúvida ou problema técnico, procure o administrador do painel.",
    ]))

    story.append(Spacer(1, 20))
    story.append(hr())
    story.append(Paragraph(f"{empresa} · Painel Comercial · {subtitulo}", styles["BodySmall"]))
    return story


def make_page_callbacks(empresa, subtitulo):
    def cover_page(canvas, doc):
        canvas.saveState()
        canvas.setFillColor(VIOLET)
        canvas.rect(0, 0, doc.pagesize[0], doc.pagesize[1], fill=1, stroke=0)
        canvas.restoreState()

    def normal_page(canvas, doc):
        canvas.saveState()
        canvas.setFillColor(ASH)
        canvas.setFont("Helvetica", 8)
        canvas.drawString(2 * cm, 1.3 * cm, f"{empresa} — {subtitulo}")
        canvas.drawRightString(doc.pagesize[0] - 2 * cm, 1.3 * cm, f"Página {doc.page - 1}")
        canvas.setStrokeColor(colors.HexColor("#E3E1F0"))
        canvas.line(2 * cm, 1.6 * cm, doc.pagesize[0] - 2 * cm, 1.6 * cm)
        canvas.restoreState()

    def on_page(canvas, doc):
        if doc.page == 1:
            cover_page(canvas, doc)
        else:
            normal_page(canvas, doc)

    return on_page


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--empresa", required=True, help="Nome da empresa, ex.: 'EdusIA Instituto'")
    ap.add_argument("--perfil", required=True, choices=["admin", "vendedor"])
    ap.add_argument("--out", required=True, help="Caminho do PDF de saída")
    ap.add_argument("--url", default="", help="URL de exemplo do painel, ex.: https://dominio.com/crm/")
    args = ap.parse_args()

    styles = build_styles()
    subtitulo = "Manual do Vendedor" if args.perfil == "vendedor" else "Manual do Usuário"
    story = build_story(styles, args.empresa, args.perfil, args.url)

    doc = SimpleDocTemplate(
        args.out, pagesize=A4,
        leftMargin=2 * cm, rightMargin=2 * cm, topMargin=2 * cm, bottomMargin=2.2 * cm,
        title=f"{subtitulo} — Painel Comercial {args.empresa}",
        author=args.empresa,
    )
    on_page = make_page_callbacks(args.empresa, subtitulo)
    doc.build(story, onFirstPage=on_page, onLaterPages=on_page)
    print("OK ->", args.out)


if __name__ == "__main__":
    main()
