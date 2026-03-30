"use client";

import { useRef, useCallback, useEffect, useState } from "react";

interface RichTextEditorProps {
	value: string;
	onChange: (html: string) => void;
	placeholder?: string;
	minHeight?: string;
}

const toolbarStyle: React.CSSProperties = {
	display: "flex",
	flexWrap: "wrap",
	gap: 6,
	padding: "8px 10px",
	background: "#f8f9fa",
	borderBottom: "1px solid #d1d5db",
};

const btnGroupStyle: React.CSSProperties = {
	display: "inline-flex",
	border: "1px solid #d1d5db",
	borderRadius: 5,
	overflow: "hidden",
	background: "#fff",
};

const btnStyle: React.CSSProperties = {
	display: "inline-flex",
	alignItems: "center",
	justifyContent: "center",
	width: 38,
	height: 36,
	border: "none",
	borderRight: "1px solid #e5e7eb",
	background: "transparent",
	color: "#374151",
	fontSize: 16,
	cursor: "pointer",
	padding: 0,
};

const btnLastStyle: React.CSSProperties = {
	...btnStyle,
	borderRight: "none",
};

export default function RichTextEditor({
	value,
	onChange,
	placeholder = "Write your product description here...",
	minHeight = "220px",
}: RichTextEditorProps) {
	const editorRef = useRef<HTMLDivElement>(null);
	const isInternalUpdate = useRef(false);
	const [fontColor, setFontColor] = useState("#000000");

	useEffect(() => {
		if (editorRef.current && !isInternalUpdate.current) {
			if (editorRef.current.innerHTML !== value) {
				editorRef.current.innerHTML = value || "";
			}
		}
		isInternalUpdate.current = false;
	}, [value]);

	const handleInput = useCallback(() => {
		if (editorRef.current) {
			isInternalUpdate.current = true;
			onChange(editorRef.current.innerHTML);
		}
	}, [onChange]);

	const exec = (command: string, val?: string) => {
		editorRef.current?.focus();
		document.execCommand(command, false, val);
		handleInput();
	};

	const handleImageUpload = () => {
		const input = document.createElement("input");
		input.type = "file";
		input.accept = "image/*";
		input.onchange = (e) => {
			const file = (e.target as HTMLInputElement).files?.[0];
			if (!file) return;
			const reader = new FileReader();
			reader.onload = () => exec("insertImage", reader.result as string);
			reader.readAsDataURL(file);
		};
		input.click();
	};

	const handleLink = () => {
		const url = prompt("Enter URL:");
		if (url) exec("createLink", url);
	};

	const Btn = ({ onClick, title, children, isLast }: { onClick: () => void; title: string; children: React.ReactNode; isLast?: boolean }) => (
		<button
			type="button"
			onClick={onClick}
			title={title}
			style={isLast ? btnLastStyle : btnStyle}
			onMouseEnter={(e) => { e.currentTarget.style.background = "#e5e7eb"; }}
			onMouseLeave={(e) => { e.currentTarget.style.background = "transparent"; }}
		>
			{children}
		</button>
	);

	const ico = 18;

	return (
		<div style={{ border: "1px solid #d1d5db", borderRadius: 6, overflow: "hidden" }}>
			{/* Toolbar */}
			<div style={toolbarStyle}>
				{/* Text Style */}
				<div style={btnGroupStyle}>
					<Btn onClick={() => exec("bold")} title="Bold"><b style={{ fontSize: 16 }}>B</b></Btn>
					<Btn onClick={() => exec("italic")} title="Italic"><i style={{ fontSize: 16, fontFamily: "Georgia, serif" }}>I</i></Btn>
					<Btn onClick={() => exec("underline")} title="Underline"><u style={{ fontSize: 16 }}>U</u></Btn>
					<Btn onClick={() => exec("strikeThrough")} title="Strikethrough" isLast><s style={{ fontSize: 16 }}>S</s></Btn>
				</div>

				{/* Font Size */}
				<div style={btnGroupStyle}>
					<select
						onChange={(e) => { if (e.target.value) exec("fontSize", e.target.value); e.target.value = ""; }}
						defaultValue=""
						title="Font Size"
						style={{ height: 36, border: "none", background: "transparent", color: "#374151", fontSize: 13, padding: "0 8px", cursor: "pointer", outline: "none" }}
					>
						<option value="" disabled>Font Size</option>
						<option value="1">Small</option>
						<option value="3">Normal</option>
						<option value="5">Large</option>
						<option value="7">Huge</option>
					</select>
				</div>

				{/* Color — underline changes to selected color */}
				<div style={btnGroupStyle}>
					<div
						style={{ position: "relative", width: 38, height: 36, display: "flex", alignItems: "center", justifyContent: "center", cursor: "pointer" }}
						title="Text Color"
					>
						<span style={{ fontWeight: 800, fontSize: 18, color: "#374151", pointerEvents: "none", zIndex: 1, borderBottom: `3px solid ${fontColor}`, paddingBottom: 1 }}>A</span>
						<input
							type="color"
							value={fontColor}
							onChange={(e) => {
								setFontColor(e.target.value);
								exec("foreColor", e.target.value);
							}}
							style={{ position: "absolute", opacity: 0, width: "100%", height: "100%", cursor: "pointer", top: 0, left: 0 }}
						/>
					</div>
				</div>

				{/* Lists */}
				<div style={btnGroupStyle}>
					<Btn onClick={() => exec("insertUnorderedList")} title="Bullet List">
						<svg width={ico} height={ico} viewBox="0 0 24 24" fill="currentColor"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/></svg>
					</Btn>
					<Btn onClick={() => exec("insertOrderedList")} title="Numbered List" isLast>
						<svg width={ico} height={ico} viewBox="0 0 24 24" fill="currentColor"><path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/></svg>
					</Btn>
				</div>

				{/* Heading */}
				<div style={btnGroupStyle}>
					<Btn onClick={() => exec("formatBlock", "h2")} title="Heading">
						<span style={{ fontWeight: 900, fontSize: 16 }}>H</span>
					</Btn>
					<Btn onClick={() => exec("formatBlock", "p")} title="Normal Text" isLast>
						<span style={{ fontSize: 14, fontWeight: 500 }}>P</span>
					</Btn>
				</div>

				{/* Link & Image */}
				<div style={btnGroupStyle}>
					<Btn onClick={handleLink} title="Insert Link">
						<svg width={ico} height={ico} viewBox="0 0 24 24" fill="currentColor"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
					</Btn>
					<Btn onClick={handleImageUpload} title="Insert Image" isLast>
						<svg width={ico} height={ico} viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
					</Btn>
				</div>

				{/* Clear */}
				<div style={btnGroupStyle}>
					<Btn onClick={() => exec("removeFormat")} title="Clear Formatting" isLast>
						<svg width={ico} height={ico} viewBox="0 0 24 24" fill="currentColor"><path d="M3.27 5L2 6.27l6.97 6.97L6.5 19h3l1.57-3.66L16.73 21 18 19.73 3.27 5zM6 5v.18L8.82 8h2.4l-.72 1.68 2.1 2.1L14.21 8H20V5H6z"/></svg>
					</Btn>
				</div>
			</div>

			{/* Editor */}
			<div
				ref={editorRef}
				contentEditable
				onInput={handleInput}
				className="product-description"
				style={{
					minHeight,
					padding: 16,
					fontSize: 14,
					color: "#333",
					outline: "none",
					background: "#fff",
					lineHeight: 1.7,
				}}
				suppressContentEditableWarning
			/>

			{!value && (
				<style>{`
					.product-description:empty::before {
						content: "${placeholder}";
						color: #9ca3af;
						pointer-events: none;
						font-style: italic;
					}
				`}</style>
			)}
		</div>
	);
}
