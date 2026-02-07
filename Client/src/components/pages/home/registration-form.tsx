// "use client"

// import type React from "react"

// import { useState } from "react"
// import { Button } from "@/components/ui/button"
// import { Input } from "@/components/ui/input"
// import { Card, CardContent, CardHeader } from "@/components/ui/card"
// import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
// import { Label } from "@/components/ui/label"

// export function RegistrationForm() {
//   const [formData, setFormData] = useState({
//     name: "",
//     phone: "",
//     referCode: "",
//     password: "",
//     confirmPassword: "",
//   })

//   const handleInputChange = (field: string, value: string) => {
//     setFormData((prev) => ({ ...prev, [field]: value }))
//   }

//   const handleSubmit = (e: React.FormEvent) => {
//     e.preventDefault()
//     console.log("Registration data:", formData)
//   }

//   return (
//     <Card className="w-full max-w-md mx-auto bg-white shadow-lg">
//       <CardHeader className="text-center pb-4">
//         <div className="flex items-center justify-center mb-4">
//           <div className="bg-pink-600 p-2 rounded-lg mr-2">
//             <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
//               <path d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7Z" />
//             </svg>
//           </div>
//           <div>
//             <h1 className="text-2xl font-bold text-pink-600">SELFSHOP</h1>
//             <p className="text-xs text-gray-500">No #1 Reseller Platform in Bangladesh</p>
//           </div>
//         </div>
//         <p className="text-sm text-gray-600">Use your correct email and password to log in to the admin panel.</p>
//       </CardHeader>

//       <CardContent>
//         <Tabs defaultValue="reseller" className="w-full">
//           <TabsList className="grid w-full grid-cols-2 mb-6">
//             <TabsTrigger value="reseller" className="data-[state=active]:bg-pink-600 data-[state=active]:text-white">
//               Login as Reseller
//             </TabsTrigger>
//             <TabsTrigger value="supplier" className="data-[state=active]:bg-pink-600 data-[state=active]:text-white">
//               Login as Supplier
//             </TabsTrigger>
//           </TabsList>

//           <TabsContent value="reseller">
//             <form onSubmit={handleSubmit} className="space-y-4">
//               <div className="space-y-2">
//                 <Label htmlFor="name" className="text-sm font-medium">
//                   Name <span className="text-red-500">*</span>
//                 </Label>
//                 <Input
//                   id="name"
//                   type="text"
//                   placeholder="Enter your name..."
//                   value={formData.name}
//                   onChange={(e) => handleInputChange("name", e.target.value)}
//                   className="border-gray-300"
//                   required
//                 />
//               </div>

//               <div className="space-y-2">
//                 <Label htmlFor="phone" className="text-sm font-medium">
//                   Phone <span className="text-red-500">*</span>
//                 </Label>
//                 <Input
//                   id="phone"
//                   type="tel"
//                   placeholder="Enter your phone..."
//                   value={formData.phone}
//                   onChange={(e) => handleInputChange("phone", e.target.value)}
//                   className="border-gray-300"
//                   required
//                 />
//               </div>

//               <div className="space-y-2">
//                 <Label htmlFor="referCode" className="text-sm font-medium">
//                   Refer code(optional) <span className="text-red-500">*</span>
//                 </Label>
//                 <Input
//                   id="referCode"
//                   type="text"
//                   placeholder="Enter your refer code..."
//                   value={formData.referCode}
//                   onChange={(e) => handleInputChange("referCode", e.target.value)}
//                   className="border-gray-300"
//                 />
//               </div>

//               <div className="space-y-2">
//                 <Label htmlFor="password" className="text-sm font-medium">
//                   Password <span className="text-red-500">*</span>
//                 </Label>
//                 <Input
//                   id="password"
//                   type="password"
//                   placeholder="Enter your password..."
//                   value={formData.password}
//                   onChange={(e) => handleInputChange("password", e.target.value)}
//                   className="border-gray-300"
//                   required
//                 />
//               </div>

//               <div className="space-y-2">
//                 <Label htmlFor="confirmPassword" className="text-sm font-medium">
//                   Confirm Password <span className="text-red-500">*</span>
//                 </Label>
//                 <Input
//                   id="confirmPassword"
//                   type="password"
//                   placeholder="Enter your confirm password..."
//                   value={formData.confirmPassword}
//                   onChange={(e) => handleInputChange("confirmPassword", e.target.value)}
//                   className="border-gray-300"
//                   required
//                 />
//               </div>

//               <Button type="submit" className="w-full bg-pink-600 hover:bg-pink-700 text-white py-3 mt-6">
//                 Registration
//               </Button>
//             </form>
//           </TabsContent>

//           <TabsContent value="supplier">
//             <div className="text-center py-8 text-gray-500">Supplier registration form would go here</div>
//           </TabsContent>
//         </Tabs>

//         <div className="mt-6">
//           <div className="relative">
//             <div className="absolute inset-0 flex items-center">
//               <span className="w-full border-t border-gray-300" />
//             </div>
//             <div className="relative flex justify-center text-sm">
//               <span className="px-2 bg-white text-gray-500">Or</span>
//             </div>
//           </div>

//           <div className="mt-4 space-y-3">
//             <Button
//               variant="outline"
//               className="w-full flex items-center justify-center gap-2 py-3 border-gray-300 bg-transparent"
//             >
//               <svg width="18" height="18" viewBox="0 0 24 24">
//                 <path
//                   fill="#4285F4"
//                   d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
//                 />
//                 <path
//                   fill="#34A853"
//                   d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
//                 />
//                 <path
//                   fill="#FBBC05"
//                   d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
//                 />
//                 <path
//                   fill="#EA4335"
//                   d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
//                 />
//               </svg>
//               Continue with Google
//             </Button>

//             <Button
//               variant="outline"
//               className="w-full flex items-center justify-center gap-2 py-3 border-gray-300 bg-transparent"
//             >
//               <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
//                 <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.024-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24c6.624 0 11.99-5.367 11.99-11.987C24.007 5.367 18.641.001 12.017.001z" />
//               </svg>
//               Continue with Apple
//             </Button>
//           </div>
//         </div>
//       </CardContent>
//     </Card>
//   )
// }
