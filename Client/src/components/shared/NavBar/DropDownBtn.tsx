"use client"

import Link from "next/link"
import { useState, useCallback, useRef, useEffect } from "react"
import { AiOutlineMenu } from "react-icons/ai"
import { MdKeyboardArrowDown, MdOutlineKeyboardArrowRight } from "react-icons/md"

// -------------------
// Types
// -------------------
interface SubSubItem {
  id: string | number
  name: string
  href?: string
  slug?: string
}

interface SubItem {
  id: string | number
  name: string
  href?: string
  sub_sub_items?: SubSubItem[]
  slug?: string
}

interface MenuItem {
  id: string | number
  name: string
  href?: string
  sub_items?: SubItem[]
  slug?: string
}

interface DropdownMenuProps {
  title: string
  menuData?: MenuItem[] | null
  className?: string
  onItemClick?: (item: MenuItem | SubItem | SubSubItem) => void
}

// -------------------
// Component
// -------------------
const DropdownMenu: React.FC<DropdownMenuProps> = ({
  title,
  menuData = defaultMenuData,
  className = "",
  onItemClick,
}) => {
  const [isOpen, setIsOpen] = useState(false)
  const [activeMenuIndex, setActiveMenuIndex] = useState<number | null>(null)
  const [activeSubMenuIndex, setActiveSubMenuIndex] = useState<number | null>(null)
  const [focusedIndex, setFocusedIndex] = useState<number>(-1)

  const dropdownRef = useRef<HTMLDivElement>(null)
  const buttonRef = useRef<HTMLButtonElement>(null)

  // -------------------
  // Event Handlers
  // -------------------
  const openDropdown = useCallback(() => {
    setIsOpen(true)
    setFocusedIndex(-1)
  }, [])

  const closeDropdown = useCallback(() => {
    setIsOpen(false)
    setActiveMenuIndex(null)
    setActiveSubMenuIndex(null)
    setFocusedIndex(-1)
  }, [])

  const toggleDropdown = useCallback(() => {
    setIsOpen((prev) => !prev)
  }, [])

  const handleMenuHover = useCallback((menuIndex: number) => {
    setActiveMenuIndex(menuIndex)
    setActiveSubMenuIndex(null)
  }, [])

  const handleSubMenuHover = useCallback((subIndex: number) => {
    setActiveSubMenuIndex(subIndex)
  }, [])

  const handleItemClick = useCallback(
    (item: MenuItem | SubItem | SubSubItem) => {
      onItemClick?.(item)
      closeDropdown()
    },
    [onItemClick, closeDropdown],
  )

  // -------------------
  // Helpers
  // -------------------
  const getItemHref = (item: MenuItem | SubItem | SubSubItem) =>
    item.href || `/product-filter?product-filter=${item.slug}`

  // -------------------
  // Effects
  // -------------------
  // Keyboard navigation
  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      if (!isOpen || !menuData?.length) return

      switch (event.key) {
        case "Escape":
          closeDropdown()
          buttonRef.current?.focus()
          break
        case "ArrowDown":
          event.preventDefault()
          setFocusedIndex((prev) => (prev < menuData.length - 1 ? prev + 1 : 0))
          break
        case "ArrowUp":
          event.preventDefault()
          setFocusedIndex((prev) => (prev > 0 ? prev - 1 : menuData.length - 1))
          break
        case "Enter":
        case " ":
          event.preventDefault()
          if (focusedIndex >= 0 && menuData[focusedIndex]) {
            handleItemClick(menuData[focusedIndex])
          }
          break
      }
    }

    if (isOpen) {
      document.addEventListener("keydown", handleKeyDown)
      return () => document.removeEventListener("keydown", handleKeyDown)
    }
  }, [isOpen, focusedIndex, menuData, closeDropdown, handleItemClick])

  // -------------------
  // Render Functions
  // -------------------
  const renderSubSubMenu = (subSubItems?: SubSubItem[]) => {
    if (!subSubItems?.length) return null

    return (
      <div className="absolute left-full top-0 min-w-[200px] z-20">
        <div className="mx-2 bg-white border border-gray-200 rounded-lg shadow-lg">
          <ul role="menu" className="py-2">
            {subSubItems.map((child) => (
              <li key={child.id} role="none">
                <Link
                  href={getItemHref(child)}
                  onClick={() => handleItemClick(child)}
                  className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-pink-600 transition-colors duration-150"
                  role="menuitem"
                >
                  {child.name}
                </Link>
              </li>
            ))}
          </ul>
        </div>
      </div>
    )
  }

  const renderSubMenu = (subItems?: SubItem[], menuIndex?: number) => {
    if (!subItems?.length || menuIndex === undefined) return null

    return (
      <div className="absolute left-full top-0 min-w-[200px] z-20">
        <div className="mx-2 bg-white border border-gray-200 rounded-lg shadow-lg">
          <ul role="menu" className="py-2">
            {subItems.map((sub, subIndex) => (
              <li
                key={sub.id}
                role="none"
                className="relative"
                onMouseEnter={() => handleSubMenuHover(subIndex)}
                onMouseLeave={() => setActiveSubMenuIndex(null)}
              >
                <Link
                  href={getItemHref(sub)}
                  onClick={() => handleItemClick(sub)}
                  className={`flex items-center justify-between px-4 py-2 text-sm transition-colors duration-150
                    ${
                      activeSubMenuIndex === subIndex
                        ? "text-pink-600 bg-gray-50"
                        : "text-gray-700 hover:bg-gray-50 hover:text-pink-600"
                    }`}
                  role="menuitem"
                >
                  <span>{sub.name}</span>
                  {sub.sub_sub_items?.length ? <MdOutlineKeyboardArrowRight size={18} /> : ""}
                </Link>
                {activeSubMenuIndex === subIndex && renderSubSubMenu(sub.sub_sub_items)}
              </li>
            ))}
          </ul>
        </div>
      </div>
    )
  }

  const renderMenu = () => {
    if (!menuData?.length) return null

    return (
      <div
        className="absolute top-full left-0 w-full z-50"
        role="menu"
        aria-orientation="vertical"
        aria-labelledby="dropdown-button"
      >
        <div className="bg-white border border-gray-200 rounded-lg shadow-lg">
          <ul className="py-2">
            {menuData.map((menu, menuIndex) => (
              <li
                key={menu.id}
                role="none"
                className={`relative ${focusedIndex === menuIndex ? "bg-gray-50" : ""}`}
                onMouseEnter={() => handleMenuHover(menuIndex)}
              >
                <Link
                  href={getItemHref(menu)}
                  onClick={() => handleItemClick(menu)}
                  className={`flex items-center justify-between px-5 py-3 text-sm transition-colors duration-150
                    ${
                      activeMenuIndex === menuIndex
                        ? "text-pink-600 bg-gray-50"
                        : "text-gray-700 hover:bg-gray-50 hover:text-pink-600"
                    }`}
                  role="menuitem"
                >
                  <span className="font-medium">{menu.name}</span>
                  {menu.sub_items?.length ? <MdOutlineKeyboardArrowRight size={18} /> : ""}
                </Link>
                {activeMenuIndex === menuIndex && renderSubMenu(menu.sub_items, menuIndex)}
              </li>
            ))}
          </ul>
        </div>
      </div>
    )
  }

  return (
    <div
      className={`relative ${className} h-13`}
      ref={dropdownRef}
      onMouseLeave={closeDropdown} // ✅ closes everything when leaving button + menu
    >
      {/* Trigger Button */}
      <button
        ref={buttonRef}
        type="button"
        id="dropdown-button"
        aria-haspopup="true"
        aria-expanded={isOpen}
        onClick={toggleDropdown}
        onMouseEnter={openDropdown}
        className="flex items-center justify-between w-[230px] h-11 ps-5 pe-3 
          bg-white border border-gray-200 rounded-xl shadow-sm
          hover:bg-gray-50 hover:border-gray-300 
          focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2
          transition-all duration-150 cursor-pointer"
      >
        <div className="flex items-center gap-2">
          <AiOutlineMenu className="text-gray-600" size={18} />
          <span className="font-semibold text-gray-900 text-lg whitespace-nowrap">{title}</span>
        </div>
        <MdKeyboardArrowDown
          size={20}
          className={`text-gray-600 transition-transform duration-200 ${isOpen ? "rotate-180" : ""}`}
        />
      </button>

      {/* Menu */}
      {isOpen && renderMenu()}
    </div>
  )
}

// -------------------
// Example Data
// -------------------
const defaultMenuData: MenuItem[] = [
  {
    id: 1,
    name: "Electronics",
    sub_items: [
      {
        id: 11,
        name: "Mobiles",
        sub_sub_items: [
          { id: 111, name: "iPhone" },
          { id: 112, name: "Samsung" },
          { id: 113, name: "Google Pixel" },
        ],
      },
      {
        id: 12,
        name: "Laptops",
        sub_sub_items: [
          { id: 121, name: "MacBook" },
          { id: 122, name: "Dell" },
          { id: 123, name: "HP" },
        ],
      },
    ],
  },
  {
    id: 2,
    name: "Fashion",
    sub_items: [
      {
        id: 21,
        name: "Men's Wear",
        sub_sub_items: [
          { id: 211, name: "Shirts" },
          { id: 212, name: "Jeans" },
        ],
      },
    ],
  },
]

export default DropdownMenu
