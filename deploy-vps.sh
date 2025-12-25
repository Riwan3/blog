#!/bin/bash

# ============================================
# VPS Deployment Helper Script
# ============================================

echo "=========================================="
echo "  Blog Deployment Script"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "${YELLOW}ℹ${NC} $1"
}

# Check if running on VPS
if [ ! -f "src/config.php" ]; then
    print_error "File src/config.php tidak ditemukan!"
    print_info "Apakah Anda sudah setup config.php dari config.php.example?"
    read -p "Setup config.php sekarang? (y/n): " setup_config

    if [ "$setup_config" == "y" ]; then
        if [ -f "src/config.php.example" ]; then
            cp src/config.php.example src/config.php
            print_success "File config.php berhasil dibuat dari template"
            print_warning "EDIT FILE src/config.php dan sesuaikan dengan kredensial database VPS!"
            nano src/config.php
        else
            print_error "Template config.php.example tidak ditemukan!"
            exit 1
        fi
    else
        exit 1
    fi
fi

# Check if seed.php exists
if [ ! -f "seed.php" ]; then
    print_error "File seed.php tidak ditemukan!"
    print_info "File seeder dibutuhkan untuk membuat user admin"
    read -p "Setup seed.php sekarang? (y/n): " setup_seed

    if [ "$setup_seed" == "y" ]; then
        if [ -f "seed.example.php" ]; then
            cp seed.example.php seed.php
            print_success "File seed.php berhasil dibuat dari template"
            print_warning "EDIT FILE seed.php dan ganti password admin!"
            nano seed.php
        else
            print_error "Template seed.example.php tidak ditemukan!"
            exit 1
        fi
    else
        exit 1
    fi
fi

echo ""
print_info "Persiapan deployment selesai!"
echo ""

# Menu
echo "Pilih aksi yang ingin dilakukan:"
echo "1) Fresh Migration (Drop all tables + Create new + Seed admin)"
echo "2) Run Migration Only (Create tables)"
echo "3) Run Seeder Only (Create admin user)"
echo "4) Set File Permissions"
echo "5) Exit"
echo ""
read -p "Pilih (1-5): " choice

case $choice in
    1)
        echo ""
        print_warning "PERINGATAN: Ini akan menghapus SEMUA data di database!"
        read -p "Apakah Anda yakin? Ketik 'YES' untuk melanjutkan: " confirm

        if [ "$confirm" == "YES" ]; then
            php migrate.php fresh --seed
            print_success "Fresh migration selesai!"
        else
            print_error "Migration dibatalkan"
        fi
        ;;
    2)
        php migrate.php
        print_success "Migration selesai!"
        ;;
    3)
        php migrate.php seed
        print_success "Seeder selesai!"
        ;;
    4)
        echo ""
        print_info "Setting file permissions..."

        # Set ownership to web server user
        if command -v www-data &> /dev/null; then
            sudo chown -R www-data:www-data .
        elif command -v apache &> /dev/null; then
            sudo chown -R apache:apache .
        elif command -v nginx &> /dev/null; then
            sudo chown -R nginx:nginx .
        else
            print_warning "Web server user tidak dikenali, skip ownership"
        fi

        # Set file permissions
        find . -type f -exec chmod 644 {} \;
        find . -type d -exec chmod 755 {} \;

        # Make scripts executable
        chmod +x migrate.php
        chmod +x deploy-vps.sh

        print_success "File permissions berhasil diatur!"
        ;;
    5)
        print_info "Exit"
        exit 0
        ;;
    *)
        print_error "Pilihan tidak valid!"
        exit 1
        ;;
esac

echo ""
echo "=========================================="
print_success "Deployment selesai!"
echo "=========================================="
