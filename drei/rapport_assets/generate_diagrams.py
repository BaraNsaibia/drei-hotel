# -*- coding: utf-8 -*-
"""Professional UML-style diagrams for the Drei Hotel project report."""
import os
from matplotlib import pyplot as plt
from matplotlib.patches import FancyBboxPatch, FancyArrowPatch, Circle, Rectangle
import matplotlib.patches as mpatches

OUT = os.path.dirname(os.path.abspath(__file__))
PRIMARY = "#315A72"
DARK = "#244356"
ACCENT = "#C79854"
BEIGE = "#F7F3ED"
CARD = "#FFFFFF"
MUTED = "#607D8B"
BORDER = "#CBB69B"
LIGHT = "#E8D8C3"
TEXT = "#263238"

plt.rcParams["font.family"] = "DejaVu Sans"
plt.rcParams["axes.unicode_minus"] = False


def save(fig, name):
    path = os.path.join(OUT, name)
    fig.savefig(path, dpi=180, bbox_inches="tight", facecolor=BEIGE, edgecolor="none")
    plt.close(fig)
    print("wrote", path)


def box(ax, x, y, w, h, title, lines, fc=CARD, ec=PRIMARY, title_c=DARK):
    p = FancyBboxPatch((x, y), w, h, boxstyle="round,pad=0.02,rounding_size=0.08",
                       linewidth=1.4, edgecolor=ec, facecolor=fc, zorder=2)
    ax.add_patch(p)
    ax.text(x + w / 2, y + h - 0.18, title, ha="center", va="center",
            fontsize=9.5, fontweight="bold", color=title_c, zorder=3)
    ax.plot([x + 0.12, x + w - 0.12], [y + h - 0.32, y + h - 0.32], color=ACCENT, lw=1.2, zorder=3)
    for i, line in enumerate(lines):
        ax.text(x + 0.12, y + h - 0.52 - i * 0.22, line, ha="left", va="center",
                fontsize=7.6, color=TEXT, zorder=3, family="DejaVu Sans")


def arrow(ax, x1, y1, x2, y2, text=None):
    ax.annotate("", xy=(x2, y2), xytext=(x1, y1),
                arrowprops=dict(arrowstyle="-|>", color=PRIMARY, lw=1.5), zorder=1)
    if text:
        ax.text((x1 + x2) / 2 + 0.08, (y1 + y2) / 2 + 0.08, text,
                fontsize=7, color=MUTED, style="italic")


def fig_architecture():
    fig, ax = plt.subplots(figsize=(11.5, 7.2))
    ax.set_xlim(0, 12)
    ax.set_ylim(0, 7.4)
    ax.axis("off")
    ax.set_title("Architecture logicielle — Drei Hotel", fontsize=14, color=DARK, pad=8, fontweight="bold")

    layers = [
        (0.4, 5.7, 11.2, 1.4, "Couche présentation (Twig + Bootstrap 5 + CSS custom)",
         "Pages publiques · Formulaires Symfony · Espace client · Back-office admin"),
        (0.4, 3.7, 11.2, 1.6, "Couche application (Contrôleurs Symfony 6.4)",
         "Home · Room · Reservation · Security · Account · AdminDashboard · AdminRoom · AdminReservation · AdminUser"),
        (0.4, 2.0, 11.2, 1.35, "Couche métier & persistance",
         "ReservationManager  ·  Formulaires & Validator  ·  Doctrine ORM  ·  Repositories"),
        (0.4, 0.35, 11.2, 1.25, "Couche données",
         "MySQL / MariaDB  —  tables users, rooms, reservations  ·  Doctrine Migrations"),
    ]
    colors = ["#EAF2F6", "#D9E6ED", "#F4EBDA", "#EEE6DC"]
    for (x, y, w, h, title, sub), c in zip(layers, colors):
        p = FancyBboxPatch((x, y), w, h, boxstyle="round,pad=0.03,rounding_size=0.1",
                           linewidth=1.6, edgecolor=PRIMARY, facecolor=c)
        ax.add_patch(p)
        ax.text(x + 0.25, y + h - 0.4, title, fontsize=11, fontweight="bold", color=DARK)
        ax.text(x + 0.25, y + 0.35, sub, fontsize=9, color=TEXT)
    for y in (5.7, 3.7, 2.0):
        ax.annotate("", xy=(6, y + 0.02), xytext=(6, y - 0.32),
                    arrowprops=dict(arrowstyle="<|-|>", color=ACCENT, lw=1.8))
    save(fig, "diag_architecture.png")


def fig_usecase():
    fig, ax = plt.subplots(figsize=(11.8, 8.2))
    ax.set_xlim(0, 12)
    ax.set_ylim(0, 8.4)
    ax.axis("off")
    ax.set_title("Diagramme de cas d'utilisation", fontsize=14, color=DARK, pad=6, fontweight="bold")

    sys = FancyBboxPatch((2.3, 0.35), 7.5, 7.6, boxstyle="round,pad=0.02,rounding_size=0.12",
                         linewidth=1.5, edgecolor=PRIMARY, facecolor="#FBF8F3")
    ax.add_patch(sys)
    ax.text(6.05, 7.65, "Système Drei Hotel", ha="center", fontsize=11, fontweight="bold", color=PRIMARY)

    def actor(x, y, name):
        ax.add_patch(Circle((x, y + 0.55), 0.18, fill=False, edgecolor=DARK, lw=1.4))
        ax.plot([x, x], [y + 0.37, y + 0.05], color=DARK, lw=1.4)
        ax.plot([x - 0.22, x + 0.22], [y + 0.28, y + 0.28], color=DARK, lw=1.4)
        ax.plot([x, x - 0.18], [y + 0.05, y - 0.22], color=DARK, lw=1.4)
        ax.plot([x, x + 0.18], [y + 0.05, y - 0.22], color=DARK, lw=1.4)
        ax.text(x, y - 0.45, name, ha="center", fontsize=8.5, color=DARK, fontweight="bold")

    actor(0.9, 5.6, "Visiteur")
    actor(0.9, 2.6, "Client\n(ROLE_USER)")
    actor(11.1, 4.2, "Administrateur\n(ROLE_ADMIN)")

    cases = [
        (3.0, 6.85, "Consulter l'accueil / à propos / services"),
        (3.0, 6.15, "Rechercher et filtrer les chambres"),
        (3.0, 5.45, "Consulter le détail d'une chambre"),
        (3.0, 4.75, "S'inscrire / se connecter"),
        (3.0, 4.05, "Créer une réservation"),
        (3.0, 3.35, "Consulter / annuler ses réservations"),
        (3.0, 2.65, "Envoyer un message de contact"),
        (6.55, 6.5, "Gérer le tableau de bord"),
        (6.55, 5.7, "CRUD des chambres"),
        (6.55, 4.9, "Suivre les réservations"),
        (6.55, 4.1, "Mettre à jour statut / paiement"),
        (6.55, 3.3, "Consulter les utilisateurs"),
    ]
    for x, y, label in cases:
        e = mpatches.Ellipse((x + 1.55, y), 3.15, 0.58, facecolor=CARD, edgecolor=PRIMARY, lw=1.2)
        ax.add_patch(e)
        ax.text(x + 1.55, y, label, ha="center", va="center", fontsize=7.2, color=TEXT)

    # associations
    links = [
        ((1.15, 6.15), (3.0, 6.85)),
        ((1.15, 6.0), (3.0, 6.15)),
        ((1.15, 5.85), (3.0, 5.45)),
        ((1.15, 5.5), (3.0, 4.75)),
        ((1.15, 3.3), (3.0, 4.75)),
        ((1.15, 3.1), (3.0, 4.05)),
        ((1.15, 2.85), (3.0, 3.35)),
        ((1.15, 5.4), (3.0, 2.65)),
        ((10.7, 4.7), (8.1, 6.5)),
        ((10.7, 4.5), (8.1, 5.7)),
        ((10.7, 4.3), (8.1, 4.9)),
        ((10.7, 4.1), (8.1, 4.1)),
        ((10.7, 3.9), (8.1, 3.3)),
    ]
    for (a, b) in links:
        ax.plot([a[0], b[0]], [a[1], b[1]], color=MUTED, lw=0.9, zorder=0)
    save(fig, "diag_usecase.png")


def fig_class():
    fig, ax = plt.subplots(figsize=(11.6, 7.4))
    ax.set_xlim(0, 12)
    ax.set_ylim(0, 7.6)
    ax.axis("off")
    ax.set_title("Diagramme de classes (extrait métier)", fontsize=14, color=DARK, pad=6, fontweight="bold")

    box(ax, 0.3, 3.2, 3.5, 3.9, "User", [
        "- id: int",
        "- email: string",
        "- roles: array",
        "- password: string (hash)",
        "- firstName / lastName",
        "- phone: string",
        "- createdAt: DateTime",
        "----------------------",
        "+ getRoles()",
        "+ isAdmin()",
        "+ getFullName()",
    ], ec=PRIMARY)
    box(ax, 8.2, 3.2, 3.5, 3.9, "Room", [
        "- id: int",
        "- name, type, description",
        "- pricePerNight: decimal",
        "- capacity, surface",
        "- image, amenities JSON",
        "- bedType, status",
        "- createdAt / updatedAt",
        "----------------------",
        "+ isAvailable()",
        "+ getTypeName()",
    ], ec=PRIMARY)
    box(ax, 4.25, 0.25, 3.5, 4.55, "Reservation", [
        "- id: int",
        "- reference: string",
        "- checkIn / checkOut",
        "- guests: int",
        "- totalPrice: decimal",
        "- status: pending|confirmed|",
        "  cancelled|completed",
        "- paymentStatus: on_site|paid",
        "- notes, createdAt",
        "----------------------",
        "+ getNumberOfNights()",
        "+ getStatusLabel()",
    ], ec=ACCENT)
    box(ax, 0.3, 0.25, 3.5, 2.6, "ReservationManager", [
        "+ calculateNights()",
        "+ calculateTotal()",
        "+ isRoomAvailable()",
        "+ createReservation()",
    ], fc="#F4EBDA", ec=ACCENT)

    ax.annotate("1", xy=(3.85, 4.4), fontsize=8, color=PRIMARY)
    ax.annotate("0..*", xy=(4.15, 3.6), fontsize=8, color=PRIMARY)
    arrow(ax, 3.8, 4.6, 4.25, 3.6)
    ax.text(3.55, 4.05, "place", fontsize=7, color=MUTED, style="italic")

    ax.annotate("1", xy=(8.05, 4.4), fontsize=8, color=PRIMARY)
    ax.annotate("0..*", xy=(7.55, 3.6), fontsize=8, color=PRIMARY)
    arrow(ax, 8.2, 4.6, 7.75, 3.6)
    ax.text(7.85, 4.05, "concerne", fontsize=7, color=MUTED, style="italic")

    arrow(ax, 2.05, 2.85, 4.25, 2.6, "utilise")
    save(fig, "diag_classes.png")


def fig_erd():
    fig, ax = plt.subplots(figsize=(11.6, 6.6))
    ax.set_xlim(0, 12)
    ax.set_ylim(0, 6.8)
    ax.axis("off")
    ax.set_title("Modèle physique — MySQL (utf8mb4)", fontsize=14, color=DARK, pad=6, fontweight="bold")

    box(ax, 0.25, 1.5, 3.6, 4.7, "users", [
        "PK  id INT AI",
        "UQ  email VARCHAR(180)",
        "    roles JSON",
        "    password VARCHAR",
        "    first_name VARCHAR(100)",
        "    last_name VARCHAR(100)",
        "    phone VARCHAR(30)",
        "    created_at DATETIME",
        "InnoDB · 18 enregistrements",
        "observés en production",
    ])
    box(ax, 8.15, 1.2, 3.6, 5.0, "rooms", [
        "PK  id INT AI",
        "    name VARCHAR(150)",
        "    type VARCHAR(50)",
        "    description TEXT",
        "    price_per_night DECIMAL(10,2)",
        "    capacity INT",
        "    image VARCHAR(255)",
        "    status VARCHAR(30)",
        "    amenities JSON",
        "    surface INT, bed_type",
        "    created_at / updated_at",
        "4 chambres catalogue",
    ])
    box(ax, 4.2, 0.35, 3.6, 5.9, "reservations", [
        "PK  id INT AI",
        "UQ  reference VARCHAR(30)",
        "FK  user_id → users.id",
        "FK  room_id → rooms.id",
        "    check_in DATE",
        "    check_out DATE",
        "    guests INT",
        "    total_price DECIMAL(10,2)",
        "    status VARCHAR(30)",
        "    payment_status VARCHAR(30)",
        "    notes TEXT NULL",
        "    created_at DATETIME",
        "7 réservations observées",
        "en environnement déployé",
    ], fc="#F4EBDA", ec=ACCENT)

    ax.annotate("", xy=(4.2, 3.6), xytext=(3.85, 3.8),
                arrowprops=dict(arrowstyle="-|>", color=PRIMARY, lw=1.5))
    ax.text(3.7, 4.15, "1 — N", fontsize=8, color=PRIMARY)
    ax.annotate("", xy=(7.8, 3.6), xytext=(8.15, 3.8),
                arrowprops=dict(arrowstyle="-|>", color=PRIMARY, lw=1.5))
    ax.text(7.55, 4.15, "N — 1", fontsize=8, color=PRIMARY)
    save(fig, "diag_erd.png")


def fig_sequence():
    fig, ax = plt.subplots(figsize=(12, 7.4))
    ax.set_xlim(0, 12.2)
    ax.set_ylim(0, 7.6)
    ax.axis("off")
    ax.set_title("Diagramme de séquence — création d'une réservation", fontsize=13.5, color=DARK, fontweight="bold")

    actors = ["Client", "Reservation\nController", "Form +\nValidator", "Reservation\nManager", "Repository", "MySQL"]
    xs = [1.0, 3.1, 5.2, 7.3, 9.2, 11.0]
    for x, name in zip(xs, actors):
        p = FancyBboxPatch((x - 0.7, 6.55), 1.4, 0.85, boxstyle="round,pad=0.02,rounding_size=0.08",
                           facecolor=CARD, edgecolor=PRIMARY, lw=1.3)
        ax.add_patch(p)
        ax.text(x, 6.95, name, ha="center", va="center", fontsize=7.4, color=DARK, fontweight="bold")
        ax.plot([x, x], [6.55, 0.4], ls="--", color=BORDER, lw=1)

    steps = [
        (0, 1, 6.25, "POST /reserver/{id}"),
        (1, 2, 5.75, "handleRequest + isValid"),
        (1, 3, 5.25, "isRoomAvailable()"),
        (3, 4, 4.75, "findOverlappingReservations"),
        (4, 5, 4.25, "SELECT chevauchements"),
        (5, 4, 3.75, "0 conflit"),
        (4, 3, 3.25, "disponible"),
        (3, 3, 2.75, "calcul nuits × prix"),
        (3, 5, 2.25, "persist + flush"),
        (1, 0, 1.55, "redirect confirmation"),
    ]
    for a, b, y, label in steps:
        x1, x2 = xs[a], xs[b]
        ax.annotate("", xy=(x2, y), xytext=(x1, y),
                    arrowprops=dict(arrowstyle="-|>", color=PRIMARY, lw=1.15))
        ax.text((x1 + x2) / 2, y + 0.12, label, ha="center", fontsize=6.8, color=TEXT)
    save(fig, "diag_sequence.png")


def fig_mvc():
    fig, ax = plt.subplots(figsize=(11.2, 5.8))
    ax.set_xlim(0, 11.2)
    ax.set_ylim(0, 5.9)
    ax.axis("off")
    ax.set_title("Pattern MVC (Symfony) appliqué au projet", fontsize=14, color=DARK, fontweight="bold")

    items = [
        (0.4, 2.1, 3.1, 2.6, "VUE\nTwig", "templates/\nbase.html.twig\nadmin_base.html.twig\nBootstrap 5"),
        (4.05, 2.1, 3.1, 2.6, "CONTRÔLEUR", "src/Controller\nHome, Room,\nReservation, Admin*"),
        (7.7, 2.1, 3.1, 2.6, "MODÈLE", "Entity + Repository\nReservationManager\nDoctrine ORM"),
    ]
    for x, y, w, h, t, s in items:
        p = FancyBboxPatch((x, y), w, h, boxstyle="round,pad=0.03,rounding_size=0.1",
                           facecolor=CARD, edgecolor=PRIMARY, lw=1.6)
        ax.add_patch(p)
        ax.text(x + w / 2, y + h - 0.55, t, ha="center", fontsize=11, fontweight="bold", color=DARK)
        ax.text(x + w / 2, y + 0.85, s, ha="center", fontsize=8.5, color=TEXT)

    ax.annotate("requête HTTP", xy=(4.05, 3.6), xytext=(3.5, 3.6),
                arrowprops=dict(arrowstyle="-|>", color=ACCENT, lw=1.6))
    ax.annotate("données", xy=(7.7, 3.6), xytext=(7.15, 3.6),
                arrowprops=dict(arrowstyle="-|>", color=ACCENT, lw=1.6))
    ax.annotate("render()", xy=(3.5, 2.3), xytext=(4.05, 2.3),
                arrowprops=dict(arrowstyle="-|>", color=PRIMARY, lw=1.4))

    p = FancyBboxPatch((2.6, 0.35), 6.0, 1.2, boxstyle="round,pad=0.03,rounding_size=0.1",
                       facecolor="#EAF2F6", edgecolor=PRIMARY, lw=1.3)
    ax.add_patch(p)
    ax.text(5.6, 0.95, "Navigateur  ↔  Symfony Security (CSRF, rôles)  ↔  MySQL",
            ha="center", fontsize=9, color=DARK)
    save(fig, "diag_mvc.png")


def fig_activity():
    fig, ax = plt.subplots(figsize=(8.8, 10.2))
    ax.set_xlim(0, 8.8)
    ax.set_ylim(0, 10.4)
    ax.axis("off")
    ax.set_title("Activité — parcours de réservation", fontsize=13.5, color=DARK, fontweight="bold")

    def node(y, text, kind="action"):
        if kind == "start":
            ax.add_patch(Circle((4.4, y), 0.18, color=DARK))
            ax.text(4.75, y, text, va="center", fontsize=8.5, color=TEXT)
        elif kind == "end":
            ax.add_patch(Circle((4.4, y), 0.22, fill=False, edgecolor=DARK, lw=2))
            ax.add_patch(Circle((4.4, y), 0.12, color=DARK))
            ax.text(4.75, y, text, va="center", fontsize=8.5, color=TEXT)
        elif kind == "decision":
            ax.fill([4.4, 6.3, 4.4, 2.5], [y + 0.38, y, y - 0.38, y], facecolor="#F4EBDA", edgecolor=ACCENT, lw=1.3)
            ax.text(4.4, y, text, ha="center", va="center", fontsize=8, color=DARK)
        else:
            p = FancyBboxPatch((2.3, y - 0.32), 4.2, 0.64, boxstyle="round,pad=0.02,rounding_size=0.1",
                               facecolor=CARD, edgecolor=PRIMARY, lw=1.2)
            ax.add_patch(p)
            ax.text(4.4, y, text, ha="center", va="center", fontsize=8.3, color=TEXT)

    ys = [9.85, 9.15, 8.4, 7.55, 6.7, 5.7, 4.85, 4.0, 3.15, 2.3, 1.45, 0.55]
    node(ys[0], "Début", "start")
    node(ys[1], "Choisir une chambre")
    node(ys[2], "Utilisateur authentifié ?", "decision")
    node(ys[3], "Inscription / connexion")
    node(ys[4], "Saisir dates, voyageurs, notes")
    node(ys[5], "Validations métier OK ?", "decision")
    node(ys[6], "Message d'erreur + nouvel essai")
    node(ys[7], "Vérifier indisponibilité / overlap")
    node(ys[8], "Chambre libre ?", "decision")
    node(ys[9], "Créer réservation pending / on_site")
    node(ys[10], "Page de confirmation + email visuel")
    node(ys[11], "Fin", "end")
    for a, b in [(0, 1), (1, 2), (3, 4), (4, 5), (7, 8), (9, 10), (10, 11)]:
        ax.annotate("", xy=(4.4, ys[b] + 0.32), xytext=(4.4, ys[a] - 0.32),
                    arrowprops=dict(arrowstyle="-|>", color=PRIMARY, lw=1.1))
    ax.annotate("non", xy=(4.4, ys[3] + 0.32), xytext=(6.45, ys[2]),
                arrowprops=dict(arrowstyle="-|>", color=ACCENT, lw=1.1), fontsize=7, color=ACCENT)
    ax.annotate("oui", xy=(4.4, ys[4] + 0.32), xytext=(4.4, ys[2] - 0.4),
                arrowprops=dict(arrowstyle="-|>", color=PRIMARY, lw=1.1))
    ax.annotate("non", xy=(6.6, ys[6]), xytext=(6.3, ys[5]),
                arrowprops=dict(arrowstyle="-|>", color=ACCENT, lw=1.1))
    ax.annotate("oui", xy=(4.4, ys[7] + 0.32), xytext=(4.4, ys[5] - 0.4),
                arrowprops=dict(arrowstyle="-|>", color=PRIMARY, lw=1.1))
    ax.annotate("non", xy=(6.6, ys[6]), xytext=(6.3, ys[8]),
                arrowprops=dict(arrowstyle="-|>", color=ACCENT, lw=1.1))
    ax.annotate("oui", xy=(4.4, ys[9] + 0.32), xytext=(4.4, ys[8] - 0.4),
                arrowprops=dict(arrowstyle="-|>", color=PRIMARY, lw=1.1))
    save(fig, "diag_activite.png")


if __name__ == "__main__":
    fig_architecture()
    fig_usecase()
    fig_class()
    fig_erd()
    fig_sequence()
    fig_mvc()
    fig_activity()
    print("done")
