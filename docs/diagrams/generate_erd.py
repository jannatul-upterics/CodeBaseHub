"""
CodeBaseHub – Database ERD Generator
Generates a high-resolution PNG (and SVG) ERD from the actual Laravel schema.
"""

import math
import os
import sys

import matplotlib
matplotlib.use("Agg")
import matplotlib.patches as mpatches
import matplotlib.pyplot as plt
import matplotlib.patheffects as pe
from matplotlib.patches import FancyBboxPatch, FancyArrowPatch
import matplotlib.lines as mlines
from matplotlib.patches import ConnectionPatch

# ──────────────────────────────────────────────────────────────────────────────
# SCHEMA DEFINITION (derived from actual Laravel migrations)
# ──────────────────────────────────────────────────────────────────────────────

TABLES = {
    "users": {
        "columns": [
            ("id",                "bigint",    "PK"),
            ("name",              "string",    ""),
            ("email",             "string",    "UK"),
            ("email_verified_at", "timestamp", "nullable"),
            ("password",          "string",    ""),
            ("remember_token",    "string",    "nullable"),
            ("created_at",        "timestamp", ""),
            ("updated_at",        "timestamp", ""),
        ],
        "color": "#1E40AF",   # blue
    },
    "websites": {
        "columns": [
            ("id",              "bigint", "PK"),
            ("website_name",    "string", ""),
            ("template_name",   "string", ""),
            ("domain",          "string", ""),
            ("description",     "text",   "nullable"),
            ("repository_url",  "string", "nullable"),
            ("deployment_url",  "string", "nullable"),
            ("notes",           "text",   "nullable"),
            ("created_at",      "timestamp", ""),
            ("updated_at",      "timestamp", ""),
        ],
        "color": "#065F46",   # green
    },
    "sessions": {
        "columns": [
            ("id",           "string",  "PK"),
            ("user_id",      "bigint",  "FK nullable"),
            ("ip_address",   "string",  "nullable"),
            ("user_agent",   "text",    "nullable"),
            ("payload",      "longtext",""),
            ("last_activity","integer", "indexed"),
        ],
        "color": "#5B21B6",   # purple
    },
    "password_reset_tokens": {
        "columns": [
            ("email",      "string",    "PK"),
            ("token",      "string",    ""),
            ("created_at", "timestamp", "nullable"),
        ],
        "color": "#B45309",   # amber
    },
    "jobs": {
        "columns": [
            ("id",           "bigint",   "PK"),
            ("queue",        "string",   "indexed"),
            ("payload",      "longtext", ""),
            ("attempts",     "smallint", ""),
            ("reserved_at",  "integer",  "nullable"),
            ("available_at", "integer",  ""),
            ("created_at",   "integer",  ""),
        ],
        "color": "#9F1239",   # rose
    },
    "job_batches": {
        "columns": [
            ("id",             "string",    "PK"),
            ("name",           "string",    ""),
            ("total_jobs",     "integer",   ""),
            ("pending_jobs",   "integer",   ""),
            ("failed_jobs",    "integer",   ""),
            ("failed_job_ids", "longtext",  ""),
            ("options",        "mediumtext","nullable"),
            ("cancelled_at",   "integer",   "nullable"),
            ("created_at",     "integer",   ""),
            ("finished_at",    "integer",   "nullable"),
        ],
        "color": "#9F1239",   # rose
    },
    "failed_jobs": {
        "columns": [
            ("id",         "bigint",   "PK"),
            ("uuid",       "string",   "UK"),
            ("connection", "string",   ""),
            ("queue",      "string",   ""),
            ("payload",    "longtext", ""),
            ("exception",  "longtext", ""),
            ("failed_at",  "timestamp",""),
        ],
        "color": "#9F1239",   # rose
    },
}

# (from_table, from_col, to_table, to_col, label)
RELATIONSHIPS = [
    ("sessions",              "user_id", "users", "id", "0..* → 1"),
    ("password_reset_tokens", "email",   "users", "email", "0..* → 1"),
]

# ──────────────────────────────────────────────────────────────────────────────
# LAYOUT  (x, y) in figure-coordinate inches from origin; each unit ≈ 1 inch
# ──────────────────────────────────────────────────────────────────────────────

TABLE_POSITIONS = {
    "users":                  (0.7,  11.0),
    "sessions":               (5.2,  11.0),
    "password_reset_tokens":  (5.2,  7.2),
    "websites":               (0.7,  7.4),
    "jobs":                   (10.5, 11.0),
    "job_batches":            (10.5, 7.2),
    "failed_jobs":            (10.5, 14.0),
}

# ──────────────────────────────────────────────────────────────────────────────
# DRAWING HELPERS
# ──────────────────────────────────────────────────────────────────────────────

TABLE_W    = 3.8   # table box width (inches in data coords since figsize=data)
ROW_H      = 0.30  # height of each row
HEADER_H   = 0.42  # header row height
FONT_MONO  = "DejaVu Sans Mono"
FONT_SANS  = "DejaVu Sans"
BG_COLOR   = "#F8FAFC"
GRID_COLOR = "#E2E8F0"
TEXT_DARK  = "#1E293B"
TEXT_MID   = "#475569"
TEXT_LIGHT = "#94A3B8"
LINE_COLOR = "#64748B"

KEY_BADGE = {
    "PK":       ("#FBBF24", "#78350F", "PK"),
    "FK":       ("#60A5FA", "#1E3A8A", "FK"),
    "UK":       ("#34D399", "#064E3B", "UK"),
    "nullable": ("#E2E8F0", "#475569", "?"),
}


def badge(ax, x, y, kind):
    """Draw a small badge label (PK, FK, UK, ?) to the left of a row."""
    bg, fg, text = KEY_BADGE.get(kind, ("#E2E8F0", "#475569", kind))
    ax.add_patch(FancyBboxPatch(
        (x, y - 0.10), 0.26, 0.20,
        boxstyle="round,pad=0.02",
        linewidth=0,
        facecolor=bg, zorder=4,
    ))
    ax.text(x + 0.13, y, text,
            ha="center", va="center",
            fontsize=5.5, fontweight="bold", color=fg,
            fontfamily=FONT_SANS, zorder=5)


def draw_table(ax, name, info, ox, oy):
    """Draw a single table box and return its bounding rect (ox,oy,w,h)."""
    cols   = info["columns"]
    color  = info["color"]
    n_rows = len(cols)
    total_h = HEADER_H + n_rows * ROW_H

    # Drop shadow
    ax.add_patch(FancyBboxPatch(
        (ox + 0.06, oy - total_h - 0.06), TABLE_W, total_h,
        boxstyle="round,pad=0.04",
        linewidth=0, facecolor="#CBD5E1", zorder=1,
    ))

    # Body
    ax.add_patch(FancyBboxPatch(
        (ox, oy - total_h), TABLE_W, total_h,
        boxstyle="round,pad=0.04",
        linewidth=1.2, edgecolor=color,
        facecolor="white", zorder=2,
    ))

    # Header band
    ax.add_patch(FancyBboxPatch(
        (ox, oy - HEADER_H), TABLE_W, HEADER_H,
        boxstyle="round,pad=0.04",
        linewidth=0, facecolor=color, zorder=3,
        clip_on=True,
    ))
    # header clip trick – draw a plain rect on top to square off the bottom
    ax.add_patch(mpatches.Rectangle(
        (ox, oy - HEADER_H), TABLE_W, HEADER_H / 2,
        linewidth=0, facecolor=color, zorder=3,
    ))

    # Table name
    ax.text(ox + TABLE_W / 2, oy - HEADER_H / 2,
            name,
            ha="center", va="center",
            fontsize=8.5, fontweight="bold", color="white",
            fontfamily=FONT_SANS, zorder=4)

    # Column rows
    for i, (col_name, col_type, col_flags) in enumerate(cols):
        row_y  = oy - HEADER_H - (i + 0.5) * ROW_H
        row_top = oy - HEADER_H - i * ROW_H

        # Alternating stripe
        stripe_color = "#F1F5F9" if i % 2 == 0 else "white"
        ax.add_patch(mpatches.Rectangle(
            (ox + 0.04, row_top - ROW_H), TABLE_W - 0.08, ROW_H,
            linewidth=0, facecolor=stripe_color, zorder=2,
        ))

        # Divider line
        ax.plot([ox + 0.04, ox + TABLE_W - 0.04],
                [row_top, row_top],
                color=GRID_COLOR, linewidth=0.5, zorder=3)

        # Badge column (leftmost 0.35 in)
        badge_x = ox + 0.06
        flags = [f.strip() for f in col_flags.split() if f.strip()]
        primary_flag = flags[0] if flags else ""
        if primary_flag in KEY_BADGE:
            badge(ax, badge_x, row_y, primary_flag)

        # Column name
        ax.text(ox + 0.38, row_y,
                col_name,
                ha="left", va="center",
                fontsize=7.0, color=TEXT_DARK,
                fontfamily=FONT_MONO, zorder=4)

        # Data type (right-aligned, muted)
        ax.text(ox + TABLE_W - 0.10, row_y,
                col_type,
                ha="right", va="center",
                fontsize=6.0, color=TEXT_LIGHT,
                fontfamily=FONT_MONO, zorder=4)

    return (ox, oy - total_h, TABLE_W, total_h)


def draw_relationship(ax, t1, c1, t2, c2, label, pos, tables_info):
    """Draw a labeled connector between two tables."""
    # We connect right edge of t1 to left edge of t2 (or adjust as needed).
    ox1, oy1 = pos[t1]
    ox2, oy2 = pos[t2]

    # Compute midpoints of right & left edges
    h1 = HEADER_H + len(tables_info[t1]["columns"]) * ROW_H
    h2 = HEADER_H + len(tables_info[t2]["columns"]) * ROW_H

    # Find row index for c1 and c2
    def row_y(table, col_name):
        cols = tables_info[table]["columns"]
        for i, (cn, _, _) in enumerate(cols):
            if cn == col_name:
                oy = pos[table][1]
                return oy - HEADER_H - (i + 0.5) * ROW_H
        # fallback: centre
        return pos[table][1] - (HEADER_H + len(cols) * ROW_H / 2)

    y1 = row_y(t1, c1)
    y2 = row_y(t2, c2)

    # Decide which sides to connect
    cx1, cx2 = ox1 + TABLE_W, ox2  # right→left default
    if ox1 > ox2:
        cx1, cx2 = ox1, ox2 + TABLE_W

    mid_x = (cx1 + cx2) / 2

    # Draw elbow connector
    ax.annotate(
        "", xy=(cx2, y2), xytext=(cx1, y1),
        arrowprops=dict(
            arrowstyle="-|>",
            color=LINE_COLOR,
            lw=1.2,
            connectionstyle="arc3,rad=0.0",
        ),
        zorder=1,
    )

    # Label
    ax.text(mid_x, (y1 + y2) / 2 + 0.12,
            label,
            ha="center", va="bottom",
            fontsize=6.0, color=TEXT_MID,
            fontfamily=FONT_SANS,
            bbox=dict(boxstyle="round,pad=0.15",
                      facecolor="white", edgecolor=GRID_COLOR, linewidth=0.8),
            zorder=5)


# ──────────────────────────────────────────────────────────────────────────────
# MAIN
# ──────────────────────────────────────────────────────────────────────────────

def main():
    out_dir  = os.path.dirname(os.path.abspath(__file__))
    png_path = os.path.join(out_dir, "codebasehub-database-erd.png")
    svg_path = os.path.join(out_dir, "codebasehub-database-erd.svg")

    FIG_W, FIG_H = 15.5, 15.8   # inches
    DPI = 200

    fig, ax = plt.subplots(figsize=(FIG_W, FIG_H), dpi=DPI)
    fig.patch.set_facecolor(BG_COLOR)
    ax.set_facecolor(BG_COLOR)

    # Subtle grid
    ax.set_xlim(0, FIG_W)
    ax.set_ylim(0, FIG_H)
    ax.set_aspect("equal")
    ax.axis("off")

    # Background watermark grid (light dots)
    for gx in [x * 0.5 for x in range(int(FIG_W / 0.5) + 1)]:
        for gy in [y * 0.5 for y in range(int(FIG_H / 0.5) + 1)]:
            ax.plot(gx, gy, ".", color=GRID_COLOR, markersize=1.5, zorder=0)

    # ── Draw relationships first (under tables) ──────────────────────────────
    for t1, c1, t2, c2, lbl in RELATIONSHIPS:
        draw_relationship(ax, t1, c1, t2, c2, lbl, TABLE_POSITIONS, TABLES)

    # ── Draw tables ──────────────────────────────────────────────────────────
    for name, info in TABLES.items():
        ox, oy = TABLE_POSITIONS[name]
        draw_table(ax, name, info, ox, oy)

    # ── Title ─────────────────────────────────────────────────────────────────
    ax.text(FIG_W / 2, FIG_H - 0.45,
            "CodeBaseHub  —  Database Architecture",
            ha="center", va="center",
            fontsize=17, fontweight="bold", color=TEXT_DARK,
            fontfamily=FONT_SANS, zorder=10)

    ax.plot([1.0, FIG_W - 1.0], [FIG_H - 0.75, FIG_H - 0.75],
            color="#CBD5E1", linewidth=1.0, zorder=10)

    # ── Legend ────────────────────────────────────────────────────────────────
    # Legend – bottom-left corner (below websites table, clear of all tables)
    legend_x = 0.50
    legend_y = 1.0
    legend_items = [
        ("#FBBF24", "#78350F", "PK  –  Primary Key"),
        ("#60A5FA", "#1E3A8A", "FK  –  Foreign Key"),
        ("#34D399", "#064E3B", "UK  –  Unique Key"),
        ("#E2E8F0", "#475569", "?    –  Nullable"),
    ]
    legend_h = len(legend_items) * 0.36 + 0.55 + 0.42   # +0.42 for arrow row
    ax.add_patch(FancyBboxPatch(
        (legend_x - 0.20, legend_y - 0.15),
        3.0, legend_h,
        boxstyle="round,pad=0.10",
        linewidth=1.2, edgecolor=GRID_COLOR,
        facecolor="white", zorder=8,
    ))
    ax.text(legend_x + 1.30, legend_y + legend_h - 0.25,
            "LEGEND",
            ha="center", va="center",
            fontsize=8.0, fontweight="bold", color=TEXT_DARK,
            fontfamily=FONT_SANS, zorder=9)
    ax.plot([legend_x - 0.05, legend_x + 2.75],
            [legend_y + legend_h - 0.42, legend_y + legend_h - 0.42],
            color=GRID_COLOR, linewidth=0.8, zorder=9)
    for j, (bg, fg, text) in enumerate(legend_items):
        lx = legend_x
        ly = legend_y + (len(legend_items) - 1 - j) * 0.36 + 0.42
        ax.add_patch(FancyBboxPatch(
            (lx, ly - 0.10), 0.30, 0.23,
            boxstyle="round,pad=0.02",
            linewidth=0, facecolor=bg, zorder=9,
        ))
        ax.text(lx + 0.15, ly + 0.01,
                text[:2].strip(),
                ha="center", va="center",
                fontsize=6.0, fontweight="bold", color=fg,
                fontfamily=FONT_SANS, zorder=10)
        ax.text(lx + 0.42, ly + 0.01,
                text[5:].strip(),
                ha="left", va="center",
                fontsize=7.5, color=TEXT_MID,
                fontfamily=FONT_SANS, zorder=10)

    # Separator between badges and arrow row
    ax.plot([legend_x - 0.05, legend_x + 2.75],
            [legend_y + 0.28, legend_y + 0.28],
            color=GRID_COLOR, linewidth=0.6, linestyle="--", zorder=9)

    # Relationship arrow legend row (inside box, at bottom)
    rel_lx = legend_x
    rel_ly = legend_y + 0.14
    ax.annotate("", xy=(rel_lx + 0.62, rel_ly),
                xytext=(rel_lx, rel_ly),
                arrowprops=dict(arrowstyle="-|>", color=LINE_COLOR, lw=1.2),
                zorder=9)
    ax.text(rel_lx + 0.70, rel_ly,
            "FK Relationship",
            ha="left", va="center",
            fontsize=7.5, color=TEXT_MID,
            fontfamily=FONT_SANS, zorder=10)

    # ── Footer ────────────────────────────────────────────────────────────────
    ax.text(FIG_W / 2, 0.22,
            "Generated from Laravel migrations  |  CodeBaseHub project  |  2026",
            ha="center", va="center",
            fontsize=7.0, color=TEXT_LIGHT,
            fontfamily=FONT_SANS, zorder=10)

    plt.tight_layout(pad=0)
    fig.savefig(png_path, dpi=DPI, bbox_inches="tight",
                facecolor=BG_COLOR, format="png")
    fig.savefig(svg_path, bbox_inches="tight",
                facecolor=BG_COLOR, format="svg")
    plt.close(fig)

    print(f"✓  PNG saved → {png_path}")
    print(f"✓  SVG saved → {svg_path}")


if __name__ == "__main__":
    main()
