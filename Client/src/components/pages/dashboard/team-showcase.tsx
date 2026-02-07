"use client"

import { Facebook, Twitter, Linkedin, Instagram } from "lucide-react"

const teamMembers = [
  {
    id: 1,
    name: "Jayant Mandal",
    role: "Social Manager - sales and Marketing",
    image: "/professional-man-headshot.png",
    social: {
      facebook: "#",
      twitter: "#",
      linkedin: "#",
      instagram: "#",
    },
  },
  {
    id: 2,
    name: "Jayant Mandal",
    role: "Social Manager - sales and Marketing",
    image: "/professional-headshot-curly-hair.png",
    social: {
      facebook: "#",
      twitter: "#",
      linkedin: "#",
      instagram: "#",
    },
  },
  {
    id: 3,
    name: "Jayant Mandal",
    role: "Social Manager - sales and Marketing",
    image: "/headshot-man-red-shirt.png",
    social: {
      facebook: "#",
      twitter: "#",
      linkedin: "#",
      instagram: "#",
    },
  },
  {
    id: 4,
    name: "Jayant Mandal",
    role: "Social Manager - sales and Marketing",
    image: "/professional-headshot-plaid-man.png",
    social: {
      facebook: "#",
      twitter: "#",
      linkedin: "#",
      instagram: "#",
    },
  },
  {
    id: 5,
    name: "Jayant Mandal",
    role: "Social Manager - sales and Marketing",
    image: "/professional-woman-headshot.png",
    social: {
      facebook: "#",
      twitter: "#",
      linkedin: "#",
      instagram: "#",
    },
  },
  {
    id: 6,
    name: "Jayant Mandal",
    role: "Social Manager - sales and Marketing",
    image: "/placeholder-cn2hb.png",
    social: {
      facebook: "#",
      twitter: "#",
      linkedin: "#",
      instagram: "#",
    },
  },
]

export default function TeamShowcase() {
  return (
    <div className="m-4 lg:m-6 md:bg-white rounded-md">
      <div className="md:p-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {teamMembers.map((member) => (
            <div
              key={member.id}
              className="bg-white p-8 text-center border-0 shadow-sm hover:shadow-md transition-shadow duration-300"
            >
              {/* Profile Image */}
              <div className="mb-6">
                <div className="w-32 h-32 mx-auto rounded-full overflow-hidden bg-gradient-to-br from-amber-100 to-orange-200">
                  <img
                    src={"https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSyrxvJ4qI_2a5es1-MscatEiPsdvjUY6xXHA&s"}
                    alt={member.name}
                    className="w-full h-full object-cover"
                  />
                </div>
              </div>

              {/* Name and Role */}
              <div className="mb-6">
                <h3 className="text-xl font-semibold text-gray-900 mb-2">{member.name}</h3>
                <p className="text-sm text-gray-600 leading-relaxed">{member.role}</p>
              </div>

              {/* Social Media Icons */}
              <div className="flex justify-center space-x-4">
                <a
                  href={member.social.facebook}
                  className="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-blue-100 transition-colors duration-200"
                  aria-label="Facebook"
                >
                  <Facebook className="w-4 h-4 text-gray-600 hover:text-blue-600" />
                </a>
                <a
                  href={member.social.twitter}
                  className="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-blue-100 transition-colors duration-200"
                  aria-label="Twitter"
                >
                  <Twitter className="w-4 h-4 text-gray-600 hover:text-blue-400" />
                </a>
                <a
                  href={member.social.linkedin}
                  className="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-blue-100 transition-colors duration-200"
                  aria-label="LinkedIn"
                >
                  <Linkedin className="w-4 h-4 text-gray-600 hover:text-blue-700" />
                </a>
                <a
                  href={member.social.instagram}
                  className="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-pink-100 transition-colors duration-200"
                  aria-label="Instagram"
                >
                  <Instagram className="w-4 h-4 text-gray-600 hover:text-pink-600" />
                </a>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
